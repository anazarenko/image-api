<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserRegistrationType;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
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
     * @return Response|static
     */
    public function loginAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $encoder = $this->container->get('security.password_encoder');

        $user = new User();

        $form = $this->createForm(new UserRegistrationType(), $user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $token = md5(uniqid($user->getEmail(), true));
            $encoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($encoded);
            $user->setToken($token);
            $user->setRoles(array(User::ROLE_USER));

            $entityManager->persist($user);
            $entityManager->flush();

            $response = array(
                'creation_time' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'token' => $token
            );

            return View::create($response, 201);
        }

        return View::create($form, 400);
    }
}
