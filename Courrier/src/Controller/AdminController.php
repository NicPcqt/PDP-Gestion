<?php

namespace Courrier\Controller;

use Courrier\Domain\Client;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Courrier\Domain\User;
use Courrier\DAO\UserDAO;
use Courrier\Form\Type\ClientType;
use Courrier\Form\Type\CourrierType;
use Courrier\Form\Type\UserType;

class AdminController {

    public function indexAction(Application $app) {
        $courriers = $app['dao.courrier']->findAll();
        $clients = $app['dao.client']->findAll();
        $users = $app['dao.user']->findAll();
        return $app['twig']->render('admin.html.twig', array(
            'courriers' => $courriers,
            'clients' => $clients,
            'users' => $users));
    }



    public function deleteCourrierAction($id_courrier, Application $app) {
        $app['dao.courrier']->delete($id_courrier);
        $app['session']->getFlashBag()->add('success', 'Le courrier a ete supprime.');
        return $app->redirect($app['url_generator']->generate('admin'));
    }

    public function deleteClientAction($id_client, Application $app) {
        $app['dao.client']->delete($id_client);
        $app['session']->getFlashBag()->add('success', 'Le client a ete supprime.');
        // Redirect to admin home page
        return $app->redirect($app['url_generator']->generate('admin'));
    }

    public function addUserAction(Request $request, Application $app) {
        $user = new User();
        $userForm = $app['form.factory']->create(UserType::class, $user);
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            // generate a random salt value
            $salt = substr(md5(time()), 0, 23);
            $user->setSalt($salt);
            $plainPassword = $user->getPassword();
            // find the default encoder
            $encoder = $app['security.encoder.bcrypt'];
            // compute the encoded password
            $password = $encoder->encodePassword($plainPassword, $user->getSalt());
            $user->setPassword($password); 
            $app['dao.user']->save($user);
            $app['session']->getFlashBag()->add('success', 'utilisateur a ete cree.');
        }
        return $app['twig']->render('user_form.html.twig', array(
            'title' => 'New user',
            'userForm' => $userForm->createView()));
    }


    public function editUserAction($id, Request $request, Application $app) {
        $user = $app['dao.user']->find($id);
        $userForm = $app['form.factory']->create(UserType::class, $user);
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $plainPassword = $user->getPassword();
            // find the encoder for the user
            $encoder = $app['security.encoder_factory']->getEncoder($user);
            // compute the encoded password
            $password = $encoder->encodePassword($plainPassword, $user->getSalt());
            $user->setPassword($password); 
            $app['dao.user']->save($user);
            $app['session']->getFlashBag()->add('success', 'The user was successfully updated.');
        }
        return $app['twig']->render('user_form.html.twig', array(
            'title' => 'Edit user',
            'userForm' => $userForm->createView()));
    }


    public function deleteUserAction($id, Application $app) {
        // Delete all associated comments
        $app['dao.comment']->deleteAllByUser($id);
        // Delete the user
        $app['dao.user']->delete($id);
        $app['session']->getFlashBag()->add('success', 'The user was successfully removed.');
        // Redirect to admin home page
        return $app->redirect($app['url_generator']->generate('admin'));
    }
}
