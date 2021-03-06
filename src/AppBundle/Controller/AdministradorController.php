<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\TbUser;
use AppBundle\Controller\UsuarioController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Description of AdministradorController
 *
 * @author Marilia
 */
class AdministradorController extends Controller {

    public $error;
    public $logControle;
    public $em;
    public $formEditarAdministrador;
    public $formAdicionarAdministrador;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/administradores")
     */
    function administradores(Request $request) {
        if (!$this->get('session')->get('idUser')) {

            return $this->redirectToRoute('login');
        } else {
            $this->em = $this->getDoctrine()->getManager();

            $arrayAdministradores = $this->gerarArrayAdministradores();
            $this->formEditarAdministrador = UsuarioController::gerarFormulario("editar");
            $this->formAdicionarAdministrador = UsuarioController::gerarFormulario("adicionar");
            $this->formExcluirAdministrador = AdministradorController::gerarFormExcluir("excluir");
            $this->formEditarAdministrador->handleRequest($request);
            $this->formAdicionarAdministrador->handleRequest($request);
            $this->formExcluirAdministrador->handleRequest($request);
            $deleteException = false;

            if ($request->request->has($this->formEditarAdministrador->getName())) {
                if ($this->formEditarAdministrador->isSubmitted() && $this->formEditarAdministrador->isValid()) {
                    $dadosFormEditarAdministrador = $this->formEditarAdministrador->getData();
                    UsuarioController::editarUsuario($dadosFormEditarAdministrador);
                    return $this->redirectToRoute('administradores');
                }
            } else  if ($request->request->has($this->formAdicionarAdministrador->getName())){
                if ($this->formAdicionarAdministrador->isSubmitted() && $this->formAdicionarAdministrador->isValid()) {
                    $dadosFormAdicionarAdministrador = $this->formAdicionarAdministrador->getData();
                    $this->adicionarAdministrador($dadosFormAdicionarAdministrador);
                    return $this->redirectToRoute('administradores');
                }
            } else {
              if ($this->formExcluirAdministrador->isSubmitted() && $this->formExcluirAdministrador->isValid()) {
                  $dadosFormExcluirAdministrador = $this->formExcluirAdministrador->getData();
                  $deleteException = $this->excluirAdministrador($dadosFormExcluirAdministrador);
                  $arrayAdministrador = $this->gerarArrayAdministradores();
              }
            }

            return $this->render('administradores.html.twig', array('administradores' => $arrayAdministradores,
                        'formAdmin' => $this->formEditarAdministrador->createView(),
                        'formAddAmin' => $this->formAdicionarAdministrador->createView(),
                        'formExcluirUser' => $this->formExcluirAdministrador->createView(),
                        'deleteException' => $deleteException));
        }
    }

    public function gerarArrayAdministradores() {
        $arrayAdministradores = array();
        $administradores = $this->selecionarAdministradores();
        foreach ($administradores as $adminUser) {
            $arrayAdministradores[] = array(
                'idUser' => $adminUser['idUser'],
                'nmUser' => $adminUser['nmUser'],
                'nuIdentification' => $adminUser['nuIdentification'],
                'dsEmail' => $adminUser['dsEmail'],
                'nuCellphone' => $adminUser['nuCellphone']
            );
        }
        return $arrayAdministradores;
    }

    function selecionarAdministradores() {
        $idUser = $this->get('session')->get('idUser');
        $queryBuilderAdmin = $this->em->createQueryBuilder();
        $queryBuilderAdmin
                ->select('u')
                ->from('AppBundle:TbUser', 'u')
                ->where($queryBuilderAdmin->expr()->eq('u.flAdmin', "'T'"))
                ->andWhere($queryBuilderAdmin->expr()->neq('u.idUser', $idUser))
                ->getQuery()
                ->execute();
        $administradores = $queryBuilderAdmin->getQuery()->getArrayResult();

        return $administradores;
    }

    function adicionarAdministrador($dadosFormAdicionarAdministrador) {
        $this->logControle->logAdmin(print_r($dadosFormAdicionarAdministrador, true));
        $novoAdministrador = new TbUser();
        $this->logControle->logAdmin(($dadosFormAdicionarAdministrador['DsPassword']));
        if ($dadosFormAdicionarAdministrador['DsPassword'] == $dadosFormAdicionarAdministrador['DsPasswordConfirm']) {
          if($this->usuarioRegistrado($dadosFormAdicionarAdministrador['DsEmail'])){
            UsuarioController::editarUsuarioAdmin($dadosFormAdicionarAdministrador);
          }else{
            UsuarioController::persistirObjetoUsuario($novoAdministrador, $dadosFormAdicionarAdministrador, 'flAdmin', 'T');
          }
        }
    }

    public function usuarioRegistrado($email){

      $usuarioEditavel = $this->getDoctrine()
              ->getRepository('AppBundle:TbUser')
              ->findOneBy(array('dsEmail' => $email));
      if($usuarioEditavel == null){
        return false;
      }else{
        return true;
      }
    }

    function gerarFormExcluir($nomeFormulario){
      $formularioExcluirAdministradores = $this->get('form.factory')
              ->createNamedBuilder($nomeFormulario, FormType::class)
              ->add('IdUsers', HiddenType::class, array('label' => false))
              ->getForm();
      return $formularioExcluirAdministradores;
    }

    function excluirAdministrador($dadosForm){
      $this->em = $this->getDoctrine()->getManager();

      $administradores = explode(";", $dadosForm['IdUsers']);

      for ($i = 0; $i < sizeof($administradores); $i++) {
        try{
          $admin = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $administradores[$i]));

          if ($admin != null) {
            $this->em->remove($admin);
            $this->em->flush();
          }
        } catch (\Exception $exception) {
            $this->logControle->logAdmin("Exception  : " . print_r($exception->getMessage(), true));
            $deleteException = true;
            return $deleteException;
        }
      }
      $this->em->flush();
    }

    /**
     * @Route("/excluirAdministradores")
     */
    // function excluirAdministradores(Request $request) {
    //     $this->em = $this->getDoctrine()->getEntityManager();
    //     $flagGerouExcecao = false;
    //     $usuariosExcecao = array();
    //     if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
    //         $data = json_decode($request->getContent(), true);
    //         $request->request->replace(is_array($data) ? $data : array());
    //         $this->logControle->logAdmin("Excluir admnistradores : " . print_r($data, true));
    //
    //         foreach ($data['arrayAdministradores'] as $idsAdministradoresExclusao) {
    //             $this->em = $this->getDoctrine()->resetManager();
    //             $this->logControle->logAdmin("Excluir  : " . print_r($idsAdministradoresExclusao, true));
    //
    //             try {
    //                 $entity = $this->em->getRepository('AppBundle:TbUser')
    //                         ->findOneBy(array('idUser' => $idsAdministradoresExclusao));
    //                 if ($entity != null) {
    //                     $this->em->remove($entity);
    //                     $this->em->flush();
    //                 }
    //             } catch (\Exception $excpetion) {
    //                 $this->logControle->logAdmin("exception  : " . print_r($excpetion->getMessage(), true));
    //                 $flagGerouExcecao = true;
    //                 $usuariosExcecao[] = $idsAdministradoresExclusao;
    //             }
    //         }
    //
    //         $retornoRequest = array(
    //             "sucesso" => true,
    //             "usuariosExcecao" => $usuariosExcecao
    //         );
    //     } else {
    //         $retornoRequest = array(
    //             "sucesso" => false,
    //             "usuariosExcecao" => NULL
    //         );
    //     }
    //     return new JsonResponse($retornoRequest);
    // }

    /**
     * @Route("/desativarAdministradorExcecao")
     */
    function desativarAdministradorExcecao(Request $request) {
        $this->em = $this->getDoctrine()->getEntityManager();
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
            $this->logControle->logAdmin("desativar admnistradores : " . print_r($data, true));
            foreach ($data['arrayAdministradoresDesativar'] as $idsAdministradoresDesativar) {
                $this->em = $this->getDoctrine()->resetManager();
                $this->logControle->logAdmin("desativar  : " . print_r($idsAdministradoresDesativar, true));

                $objetoUsuario = $this->em->getRepository('AppBundle:TbUser')
                        ->findOneBy(array('idUser' => $idsAdministradoresDesativar));
                if ($objetoUsuario != null) {
                    $objetoUsuario->setFlAdmin('F');
                    $this->em->flush();
                }
            }
            $retornoRequest = array(
                "sucesso" => true
            );
        } else {
            $retornoRequest = array(
                "sucesso" => false
            );
        }
        return new JsonResponse($retornoRequest);
    }

}
