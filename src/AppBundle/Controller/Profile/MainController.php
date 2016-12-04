<?php

namespace AppBundle\Controller\Profile;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/profile")
 */
class MainController extends Controller
{
    /**
     * @Route("/", name="profile_main")
     */
    public function indexAction(Request $request)
    {

        return $this->render('AppBundle:profile:index.html.twig');
    }
}
