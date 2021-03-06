<?php

declare(strict_types=1);

namespace Bolt\Controller\Backend;

use Bolt\Controller\BaseController;
use Bolt\Form\ChangePasswordType;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserController.
 *
 * @Security("has_role('ROLE_ADMIN')")
 */
class UserController extends BaseController
{
    /**
     * @Route("/users", name="bolt_users")
     */
    public function users()
    {
        return $this->renderTemplate('pages/about.twig');
    }

    /**
     * @Route("/profile-edit", methods={"GET"}, name="bolt_profile_edit")
     */
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        return $this->renderTemplate('users/edit.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/profile-edit", methods={"POST"}, name="bolt_profile_edit_post")
     */
    public function edit_post(Request $request, UrlGeneratorInterface $urlGenerator, ObjectManager $manager, UserPasswordEncoderInterface $encoder): Response
    {
        $user = $this->getUser();
        $url = $urlGenerator->generate('bolt_profile_edit');
        $locale = $request->get('user')['locale'];

        $newPassword = $request->get('user')['password_first'];
        $confirmedPassword = $request->get('user')['password_second'];

        $user->setFullName($request->get('user')['fullName']);
        $user->setEmail($request->get('user')['email']);
        $user->setLocale($locale);
        $user->setbackendTheme($request->get('user')['backendTheme']);

        if (!empty($newPassword) || !empty($confirmedPassword)) {
            // Set new password
            if ($newPassword === $confirmedPassword) {
                $user->setPassword($encoder->encodePassword($user, $newPassword));
            } else {
                return $this->renderTemplate('users/edit.twig', [
                    'user' => $user,
                ]);
            }
        }

        $manager->flush();

        $request->getSession()->set('_locale', $locale);

        return new RedirectResponse($url);
    }

    /**
     * @Route("/change-password", methods={"GET", "POST"}, name="bolt_change_password")
     *
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $encoder
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     *
     * @return Response
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->encodePassword($user, $form->get('newPassword')->getData()));
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('security_logout');
        }

        return $this->renderTemplate('users/change_password.twig', [
            'form' => $form->createView(),
        ]);
    }
}
