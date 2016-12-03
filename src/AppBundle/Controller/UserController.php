<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Entity\User;
use AppBundle\Form\ImageUploadType;
use AppBundle\Form\UserRegistrationType;
use AppBundle\Model\Geolocation\GeolocationFactory;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Rest\View
     */
    public function allAction()
    {
        $users = $this->getDoctrine()->getRepository('AppBundle:User')->findBy(array('isActive' => true));

        return array('users' => $users);
    }

    /**
     * @param Request $request
     * @return static
     *
     * @Rest\View(serializerGroups={"login"})
     */
    public function loginAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $encoder = $this->container->get('security.password_encoder');

        $user = new User();

        $form = $this->createForm(new UserRegistrationType(), $user);
        $form->submit(array_merge($request->request->all(), $request->files->all()));

        if ($form->isValid()) {
            $token = md5(uniqid($user->getEmail(), true));
            $encoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($encoded);
            $user->setToken($token);
            $user->setRoles(array(User::ROLE_USER));

            // $file stores the uploaded avatar file
            /** @var UploadedFile $file */
            $file = $user->getAvatar();

            // Generate a unique name for the picture before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // Move the file to the directory where avatars are stored
            $file->move(
                $this->getParameter('avatar_directory'),
                $fileName
            );

            // Save avatar picture path
            $user->setAvatar($fileName);

            $entityManager->persist($user);
            $entityManager->flush();

            $response = array(
                'creation_time' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'token' => $token,
                'avatar' => 'http://'.$request->getHost().'/'.$this->getParameter('avatar_directory').'/'.$user->getAvatar()
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }

    /**
     * @param Request $request
     * @return static
     *
     * @Rest\View(serializerGroups={"upload"})
     */
    public function imageAction(Request $request)
    {
        $token = $request->request->get('token');
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(array('token' => $token));

        if (!$user) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

        $request->request->remove('token');

        $entityManager = $this->getDoctrine()->getManager();
        $image = new Image();

        // Create form
        $form = $this->createForm(new ImageUploadType(), $image);
        // Handle request
        $this->processForm($request, $form);

        if ($form->isValid()) {
            // $file stores the uploaded avatar file
            /** @var UploadedFile $file */
            $file = $image->getImage();

            // Generate a unique name for the picture before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // Move the file to the directory where avatars are stored
            $file->move($this->getParameter('image_directory'), $fileName);

            // Save picture path
            $image->setImage($fileName);
            $image->setBigImagePath('http://'.$request->getHost().'/'.$this->getParameter('image_directory').'/'.$fileName);

            // Get address
            $geolocator = GeolocationFactory::create(GeolocationFactory::TYPE_GOOGLE);
            $address = $geolocator->getAddress($image->getLatitude(), $image->getLongitude());
            $image->setAddress($address);

            // Get weather
            $weatherService = $this->get('app.weather_manager');
            $weatherString = $weatherService->getWeatherByCoords($image->getLatitude(), $image->getLongitude());
            $image->setWeather($weatherString);

            $entityManager->persist($image);
            $entityManager->flush();

            $response = array(
                'bigPhoto' => $image->getBigImagePath(),
                'weather' => $image->getWeather(),
                'address' => $image->getAddress(),
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     */
    private function processForm(Request $request, FormInterface $form)
    {
        $data = array_merge($request->request->all(), $request->files->all());
        $clearMissing = $request->getMethod() != 'PATCH';
        $form->submit($data, $clearMissing);
    }
}
