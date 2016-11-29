<?php

namespace AppBundle\Controller\Profile;

use AppBundle\Entity\User;
//use AppBundle\Form\UserRegistrationType;
use AppBundle\Form\UserRegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class SecurityController
 * @package AdminBundle\Controller
 *
 * @Route("/profile")
 */
class SecurityController extends Controller
{
    /**
     * @Route("/login", name="profile_login")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUserEmail = $authenticationUtils->getLastUsername();

        return $this->render(
            'AppBundle:profile/security:login.html.twig',
            array(
                'lastUserEmail' => $lastUserEmail,
                'error' => $error,
            )
        );
    }

    /**
     * @Route("/login_check", name="profile_login_check")
     */
    public function loginCheckAction()
    {

    }

    /**
     * @Route("/logout", name="profile_logout")
     */
    public function logoutCheckAction()
    {

    }

    /**
     * @Route("/registration", name="profile_registration")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function registrationAction(Request $request)
    {
        $user = new User();
        $form = $this->createRegistrationForm($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $user->setRoles(array(User::ROLE_USER));
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_DEFAULT));
            $user->setUsername($user->getEmail());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('profile_login');
        }

        return $this->render(
            'AppBundle:profile/security:registration.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\Form
     */
    private function createRegistrationForm(User $user)
    {
        $form = $this->createForm(
            UserRegistrationType::class,
            $user,
            array(
                'action' => $this->generateUrl('profile_registration'),
                'method' => 'POST'
            )
        );

        return $form;
    }
}
