<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserRegistrationType;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
}
