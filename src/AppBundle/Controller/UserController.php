<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Entity\User;
use AppBundle\Form\ImageUploadType;
use AppBundle\Form\UserRegistrationType;
use AppBundle\Model\Geolocation\GeolocationFactory;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @ApiDoc(
     *     resource=true,
     *     description="Create new user",
     *     parameters={
     *         {"name"="username", "dataType"="string", "required"=false, "description"="User name"},
     *         {"name"="email", "dataType"="email", "required"=true, "description"="User email"},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="Password"},
     *         {"name"="avatar", "dataType"="file", "required"=true, "description"="User avatar"}
     *     },
     *     statusCodes={
     *         201="Returned when user successfully created",
     *         400="Returned when incorrect request data"
     *     }
     * )
     *
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
        $this->processForm($request, $form);

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
     * @ApiDoc(
     *     resource=true,
     *     description="Add image",
     *     headers={
     *         {
     *             "name"="token",
     *             "description"="Authorization key"
     *         }
     *     },
     *     parameters={
     *         {"name"="image", "dataType"="file", "required"=true, "description"="Image"},
     *         {"name"="description", "dataType"="string", "required"=false, "description"="Image description"},
     *         {"name"="hashtag", "dataType"="string", "required"=false, "description"="Image hashtag"},
     *         {"name"="latitude", "dataType"="float", "required"=true, "description"="Image latitude coordinate"},
     *         {"name"="longitude", "dataType"="float", "required"=true, "description"="Image longitude coordinate"}
     *     },
     *     statusCodes={
     *         201="Returned when image successfully created",
     *         400="Returned when incorrect request data",
     *         403="Returned when invalid access token"
     *     }
     * )
     *
     * @param Request $request
     * @return static
     *
     * @Rest\View(serializerGroups={"upload"})
     */
    public function imageAction(Request $request)
    {
        // Validate token
        if (!$user = $this->validateToken($request)) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

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
            $fileName = md5(uniqid().$user->getId()).'.'.$file->guessExtension();

            // Move the file to the directory where pictures are stored
            $file->move($this->getParameter('image_directory'), $fileName);

            // Create small image
            $smallImage = $this
                ->get('app.image_manager')
                ->resizeImage(
                    $this->getParameter('image_directory').'/'.$fileName,
                    $this->getParameter('image_directory_small'),
                    300,
                    300
                );

            // Get sizes
            list($width_orig, $height_orig) = getimagesize($this->getParameter('image_directory').'/'.$fileName);

            // Create big image
            if ($width_orig > 1200 || $height_orig > 1200) {
                $bigImage = $this
                    ->get('app.image_manager')
                    ->resizeImage(
                        $this->getParameter('image_directory').'/'.$fileName,
                        $this->getParameter('image_directory_small'),
                        800,
                        800
                    );
            } else {
                $bigImage = $fileName;
                $fs = new Filesystem();
                $fs->copy($this->getParameter('image_directory').'/'.$fileName, $this->getParameter('image_directory_big').'/'.$fileName);
            }

            // Save picture path
            $image->setImage($fileName);
            $image->setBigImage($bigImage);
            $image->setSmallImage($smallImage);

            // Add user to image
            $image->setUser($user);

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
                'parameters' => array(
                    'address' => $image->getAddress(),
                    'weather' => $image->getWeather()
                ),
                'smallImage' => $smallImage ? 'http://'.$request->getHost().'/'.$this->getParameter('image_directory_small').'/'.$smallImage : '',
                'bigImage' => $bigImage ? 'http://'.$request->getHost().'/'.$this->getParameter('image_directory_big').'/'.$bigImage : ''
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }

    /**
     * @param Request $request
     * @return static
     *
     * @Rest\View(serializerGroups={"all"})
     */
    public function allAction(Request $request)
    {
        // Validate token
        if (!$user = $this->validateToken($request)) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

        // Get all user images
        $images = $this->getDoctrine()->getRepository('AppBundle:Image')->findBy(array('user' => $user));

        $response = array();

        foreach ($images as $image) {
            $response['images'][] = array(
                'id' => $image->getId(),
                'description' => $image->getDescription(),
                'hashtag' => $image->getHashtag(),
                'parameters' => array(
                    'longitude' => $image->getLongitude(),
                    'latitude' => $image->getLatitude(),
                    'address' => $image->getAddress(),
                    'weather' => $image->getWeather()
                ),
                'smallImage' => $image->getSmallImage() ? 'http://'.$request->getHost().'/'.$this->getParameter('image_directory_small').'/'.$image->getSmallImage() : '',
                'bigImage' => $image->getBigImage() ? 'http://'.$request->getHost().'/'.$this->getParameter('image_directory_big').'/'.$image->getBigImage() : '',
                'created' => $image->getCreatedAt()->format('d-m-Y H:i:s')
            );
        }

        return View::create($response, 200);
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

    /**
     * @param Request $request
     * @return User|null|object
     */
    private function validateToken(Request $request)
    {
        $token = $request->headers->get('token');
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(array('token' => $token));

        return $user;
    }
}
