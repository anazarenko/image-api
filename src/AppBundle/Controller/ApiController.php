<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animation;
use AppBundle\Entity\Image;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAccessToken;
use AppBundle\Form\ImageUploadType;
use AppBundle\Form\UserLoginType;
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

class ApiController extends Controller
{
    /**
     * @ApiDoc(
     *     section="Authorization",
     *     description="Create new user",
     *     parameters={
     *         {"name"="username", "dataType"="string", "required"=false, "description"="User name"},
     *         {"name"="email", "dataType"="string", "required"=true, "description"="User email"},
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
     * @return View
     *
     * @Rest\View(serializerGroups={"login"})
     */
    public function createAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(new UserRegistrationType(), $user);
        $this->processForm($request, $form);

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $imageManager = $this->get('app.image_manager');

            $user->setPassword(md5($user->getPassword()));
            $user->setRoles(array(User::ROLE_USER));

            $accessToken = new UserAccessToken();
            $accessToken->setUser($user);
            $accessToken->setAccessToken(md5(uniqid($user->getEmail(), true)));
            $accessToken->setCreatedAt(new \DateTime('now'));
            $accessToken->setExpiredAt((new \DateTime('now'))->modify('+1 month'));

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
            $entityManager->persist($accessToken);
            $entityManager->flush();

            $response = array(
                'creation_time' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'token' => $accessToken->getAccessToken(),
                'avatar' => $imageManager->getAbsolutePath(
                    $request,
                    $this->getParameter('avatar_directory'),
                    $user->getAvatar()
                )
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }

    /**
     * @ApiDoc(
     *     section="Authorization",
     *     description="Login",
     *     parameters={
     *         {"name"="email", "dataType"="string", "required"=true, "description"="User email"},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="Password"}
     *     },
     *     statusCodes={
     *         200="Returned when user successfully logged in",
     *         400="Returned when incorrect request data"
     *     }
     * )
     *
     * @param Request $request
     * @return View
     *
     * @Rest\View(serializerGroups={"login"})
     */
    public function loginAction(Request $request)
    {
        if ($request->request->get('email') && $request->request->get('password')) {
            $entityManager = $this->getDoctrine()->getManager();
            $imageManager = $this->get('app.image_manager');

            /** @var User $user */
            $user = $entityManager->getRepository('AppBundle:User')->findOneBy(
                array('email' => $request->request->get('email'), 'password' => md5($request->request->get('password')))
            );

            if (!$user) {
                return View::create(array('error' => 'Incorrect email or password'), 400);
            }

            $accessTokens = $entityManager->getRepository('AppBundle:UserAccessToken')
                ->findBy(
                    array('user' => $user->getId()),
                    array('expiredAt' => 'DESC'),
                    1
                );

            if ($accessTokens[0]->getExpiredAt() < new \DateTime('now')) {
                $accessToken = new UserAccessToken();
                $accessToken->setUser($user);
                $accessToken->setAccessToken(md5(uniqid($user->getEmail(), true)));
                $accessToken->setCreatedAt(new \DateTime('now'));
                $accessToken->setExpiredAt((new \DateTime('now'))->modify('+1 month'));
                $entityManager->persist($accessToken);
                $entityManager->flush();
            } else {
                $accessToken = $accessTokens[0];
            }

            $response = array(
                'creation_time' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'token' => $accessToken->getAccessToken(),
                'avatar' => $imageManager->getAbsolutePath(
                    $request,
                    $this->getParameter('avatar_directory'),
                    $user->getAvatar()
                )
            );

            return View::create($response, 200);
        }

        return View::create(array('error' => 'Incorrect data'), 400);
    }

    /**
     * @ApiDoc(
     *     section="Image",
     *     description="Add image",
     *     headers={
     *         {
     *             "name"="token",
     *             "description"="Authorization key",
     *             "required"=true
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
     * @return View
     *
     * @Rest\View(serializerGroups={"upload"})
     */
    public function imageAction(Request $request)
    {
        // Validate token
        if (!$user = $this->validateToken($request)) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

        $image = new Image();

        // Create form
        $form = $this->createForm(new ImageUploadType(), $image);
        // Handle request
        $this->processForm($request, $form);

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $imageManager = $this->get('app.image_manager');

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
                    $imageManager->getRelativePath($this->getParameter('image_directory'), $fileName),
                    $this->getParameter('image_directory_small'),
                    300,
                    300
                );

            // Get sizes
            list($width_orig, $height_orig) = getimagesize(
                $imageManager->getRelativePath(
                    $this->getParameter('image_directory'),
                    $fileName
                )
            );

            // Create big image
            if ($width_orig > 1200 || $height_orig > 1200) {
                $bigImage = $this
                    ->get('app.image_manager')
                    ->resizeImage(
                        $imageManager->getRelativePath($this->getParameter('image_directory'), $fileName),
                        $this->getParameter('image_directory_small'),
                        800,
                        800
                    );
            } else {
                $bigImage = $fileName;
                $fs = new Filesystem();
                $fs->copy($this->getParameter('image_directory').'/'.$fileName, $this->getParameter('image_directory_big').'/'.$fileName);
                $fs->copy(
                    $imageManager->getRelativePath($this->getParameter('image_directory'), $fileName),
                    $imageManager->getRelativePath($this->getParameter('image_directory_big'), $fileName)
                );
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
            if ($address) {
                $image->setAddress($address);
            } else {
                $address = 'The address is not available in this place';
            }

            // Get weather
            $weatherService = $this->get('app.weather_manager');
            $weatherString = $weatherService->getWeatherByCoords($image->getLatitude(), $image->getLongitude());
            if ($weatherString) {
                $image->setWeather(strtolower($weatherString));
            } else {
                $weatherString = 'The weather is not available in this place';
            }

            $entityManager->persist($image);
            $entityManager->flush();

            $response = array(
                'parameters' => array(
                    'address' => $address,
                    'weather' => $weatherString
                ),
                'smallImage' => $smallImage ? $imageManager->getAbsolutePath($request, $this->getParameter('image_directory_small'), $smallImage) : '',
                'bigImage' => $bigImage ? $imageManager->getAbsolutePath($request, $this->getParameter('image_directory_big'), $bigImage) : ''
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }

    /**
     * @ApiDoc(
     *     section="Image",
     *     description="Get all user images",
     *     headers={
     *         {
     *             "name"="token",
     *             "description"="Authorization key",
     *             "required"=true
     *         }
     *     },
     *     statusCodes={
     *         200="Successfully done",
     *         403="Returned when invalid access token"
     *     }
     * )
     *
     * @param Request $request
     * @return View
     *
     * @Rest\View(serializerGroups={"all"})
     */
    public function allAction(Request $request)
    {
        // Validate token
        if (!$user = $this->validateToken($request)) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

        $imageManager = $this->get('app.image_manager');
        $response = array('images' => []);

        foreach ($user->getImages() as $image) {
            $response['images'][] = array(
                'id' => $image->getId(),
                'description' => $image->getDescription(),
                'hashtag' => $image->getHashtag(),
                'parameters' => array(
                    'longitude' => $image->getLongitude(),
                    'latitude' => $image->getLatitude(),
                    'address' => $image->getAddress(),
                    'weather' => ucfirst($image->getWeather())
                ),
                'smallImagePath' => $image->getSmallImage() ? $imageManager->getAbsolutePath($request, $this->getParameter('image_directory_small'), $image->getSmallImage()) : '',
                'bigImagePath' => $image->getBigImage() ? $imageManager->getAbsolutePath($request, $this->getParameter('image_directory_big'), $image->getBigImage()) : '',
                'created' => $image->getCreatedAt()->format('d-m-Y H:i:s')
            );
        }

        foreach ($user->getAnimations() as $animation) {
            $response['gif'][] = array(
                'id' => $animation->getId(),
                'weather' => $animation->getWeather(),
                'path' => $animation->getImage() ? $imageManager->getAbsolutePath($request, $this->getParameter('gif_directory'), $animation->getImage()) : '',
                'created' => $animation->getCreatedAt()->format('d-m-Y H:i:s')
            );
        }

//        /** @var UserAccessToken $token */
//        foreach ($user->getAccessTokens() as $token) {
//            $response['tokens'][] = array(
//                'token' => $token->getAccessToken(),
//                'expiredAt' => $token->getExpiredAt()->format('d-m-Y')
//            );
//        }

        return View::create($response, 200);
    }

    /**
     * @ApiDoc(
     *     section="Image",
     *     description="Get gif",
     *     headers={
     *         {
     *             "name"="token",
     *             "description"="Authorization key",
     *             "required"=true
     *         }
     *     },
     *     statusCodes={
     *         200="GIF file successfully generated",
     *         403="Returned when invalid access token"
     *     },
     *     parameters={
     *         {"name"="weather", "dataType"="string", "required"=false, "description"="Weather for search images"}
     *     },
     * )
     *
     * @param Request $request
     * @return View
     *
     * @Rest\View(serializerGroups={"gif"})
     */
    public function gifAction(Request $request)
    {
        // Validate token
        if (!$user = $this->validateToken($request)) {
            return View::create(array('error' => 'Invalid access token'), 403);
        }

        $weatherParam = trim(strtolower($request->query->get('weather')));
        $imageManager = $this->get('app.image_manager');
        $entityManager = $this->getDoctrine()->getManager();

        $images = $this->getDoctrine()
            ->getRepository('AppBundle:Image')
            ->findImageByWeather($user, $weatherParam);

        $gif = $this->get('app.image_manager')->createGif($images);

        $animation = new Animation();
        $animation->setWeather($weatherParam);
        $animation->setUser($user);
        $animation->setImage($gif);

        $entityManager->persist($animation);
        $entityManager->flush();

        $response = array(
            'gif' => $imageManager->getAbsolutePath($request, $this->getParameter('gif_directory'), $gif)
        );

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
     * @return User|null|object|bool
     */
    private function validateToken(Request $request)
    {
        $token = $request->headers->get('token');
        $accessToken = $this->getDoctrine()
            ->getRepository('AppBundle:UserAccessToken')
            ->findOneBy(array('accessToken' => $token));

        if ($accessToken) {
            return $accessToken->getUser();
        }

        return false;
    }
}
