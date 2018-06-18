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
use AppBundle\Entity\TbUser;
use AppBundle\Controller\UsuarioController;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\TbClass;
use AppBundle\Entity\TbPortfolioClass;
use AppBundle\Entity\TbPortfolio;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Description of PropositorController
 *
 * @author Marilia
 */
class PropositorController extends Controller {

    public $error;
    public $logControle;
    public $em;
    public $formAdicionarPropositor;
    public $formEditarPropositor;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/propositores", name="propositores")
     */
    function propositoresAction(Request $request) {

        if (!$this->get('session')->get('idUser')) {

            return $this->redirectToRoute('login');
        } else {
            $this->em = $this->getDoctrine()->getManager();
            $arrayPropositores = $this->gerarArrayPropositores();
            $this->formEditarPropositor = UsuarioController::gerarFormulario("editar");
            $this->formAdicionarPropositor = UsuarioController::gerarFormulario("adicionar");
            $this->formEditarPropositor->handleRequest($request);
            $this->formAdicionarPropositor->handleRequest($request);

            if ($request->request->has($this->formEditarPropositor->getName())) {
                if ($this->formEditarPropositor->isSubmitted() && $this->formEditarPropositor->isValid()) {
                    $dadosFormEditarPropositor = $this->formEditarPropositor->getData();
                    UsuarioController::editarUsuario($dadosFormEditarPropositor);
                    return $this->redirectToRoute('propositores');
                }
            } else {
                if ($this->formAdicionarPropositor->isSubmitted() && $this->formAdicionarPropositor->isValid()) {
                    $dadosFormAdicionarPropositor = $this->formAdicionarPropositor->getData();
                    $this->adicionarPropositor($dadosFormAdicionarPropositor);
                    return $this->redirectToRoute('propositores');
                }
            }
            return $this->render('propositores.html.twig', array('propositores' => $arrayPropositores,
                        'formPropositor' => $this->formEditarPropositor->createView(),
                        'formAddPropositor' => $this->formAdicionarPropositor->createView()));
        }
    }

    function gerarArrayPropositores() {
        $arrayPropositores = array();
        $propositores = $this->selecionarPropositores();
        foreach ($propositores as $proposerUser) {
            $arrayPropositores[] = array(
                'idUser' => $proposerUser['idUser'],
                'nmUser' => $proposerUser['nmUser'],
                'nuIdentification' => $proposerUser['nuIdentification'],
                'dsEmail' => $proposerUser['dsEmail'],
                'nuCellphone' => $proposerUser['nuCellphone']
            );
        }
        return $arrayPropositores;
    }

    function selecionarPropositores() {
        $idUser = $this->get('session')->get('idUser');
        $queryBuilderProposer = $this->em->createQueryBuilder();
        $queryBuilderProposer
                ->select('u')
                ->from('AppBundle:TbUser', 'u')
                ->where($queryBuilderProposer->expr()->eq('u.flProposer', "'T'"))
                ->andWhere($queryBuilderProposer->expr()->neq('u.idUser', $idUser))
                ->getQuery()
                ->execute();
        $propositores = $queryBuilderProposer->getQuery()->getArrayResult();

        return $propositores;
    }

    function adicionarPropositor($dadosFormAdicionarPropositor) {
        $this->logControle->logAdmin(print_r($dadosFormAdicionarPropositor, true));
        $novoPropositor = new TbUser();
        $this->logControle->logAdmin(($dadosFormAdicionarPropositor['DsPassword']));
        if ($dadosFormAdicionarPropositor['DsPassword'] == $dadosFormAdicionarPropositor['DsPasswordConfirm']) {
          if($this->usuarioRegistrado($dadosFormAdicionarPropositor['DsEmail'])){
            UsuarioController::editarUsuarioPropositor($dadosFormAdicionarPropositor);
          }else{
            UsuarioController::persistirObjetoUsuario($novoPropositor, $dadosFormAdicionarPropositor, 'flProposer', 'T');
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

    /**
     * @Route("/excluirPropositores")
     */
    function excluirPropositores(Request $request) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $flagGerouExcecao = false;
        $usuariosExcecao = array();
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
            $this->logControle->logAdmin("Excluir propositores : " . print_r($data, true));

            foreach ($data['arrayPropositores'] as $idsAdministradoresExclusao) {
                $this->em = $this->getDoctrine()->resetManager();
                $this->logControle->logAdmin("Excluir  : " . print_r($idsAdministradoresExclusao, true));

                try {

                    $entity = $this->em->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('idUser' => $idsAdministradoresExclusao));

                    if ($entity != null) {
                        $this->em->remove($entity);
                        $this->em->flush();
                    }
                } catch (\Exception $excpetion) {
                    $this->logControle->logAdmin("exception  : " . print_r($excpetion->getMessage(), true));
                    $flagGerouExcecao = true;
                    $usuariosExcecao[] = $idsAdministradoresExclusao;
                }
            }

            $retornoRequest = array(
                "sucesso" => true,
                "usuariosExcecao" => $usuariosExcecao
            );
        } else {
            $retornoRequest = array(
                "sucesso" => false,
                "usuariosExcecao" => NULL
            );
        }
        return new JsonResponse($retornoRequest);
    }

    /**
     * @Route("/desativarPropositorExcecao")
     */
    function desativarPropositorExcecao(Request $request) {
        $this->em = $this->getDoctrine()->getEntityManager();
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
            $this->logControle->logAdmin("desativar propositores : " . print_r($data, true));
            foreach ($data['arrayPropositoresDesativar'] as $idsAdministradoresDesativar) {
                $this->em = $this->getDoctrine()->resetManager();
                $this->logControle->logAdmin("desativar  : " . print_r($idsAdministradoresDesativar, true));

                $objetoUsuario = $this->em->getRepository('AppBundle:TbUser')
                        ->findOneBy(array('idUser' => $idsAdministradoresDesativar));
                if ($objetoUsuario != null) {
                    $objetoUsuario->setFlProposer('F');
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

    /**
     * @Route("/portfolioPropositor", name="portfolioPropositor")
     */
    function portfolioPropositorAction(Request $request) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $dadosMenuLateralCadastro = MenuLateralCadastroController::carregarDadosMenuLateralCadastro(); //carrega dados do menu lateral, chamar em todas as telas que necessario e enviar nos parametros do render

        $propositor = $this->selecionarPropositorSalvo();
        $portfolios = $this->selecionarPortfoliosSalvos();

        $this->formPortProp = PropositorController::gerarFormPropositor("salvarPortfolioPropositor");
        $this->formPortProp->handleRequest($request);

        if ($request->request->has($this->formPortProp->getName())) {
            if ($this->formPortProp->isSubmitted() && $this->formPortProp->isValid()) {
                $dadosFormPortProp = $this->formPortProp->getData();
                PropositorController::editarPortProp($dadosFormPortProp);
            }
        }

        return $this->render("portfolioPropositor.html.twig", array('dadosMenuLateralCadastro' => $dadosMenuLateralCadastro, 'formPortProp' => $this->formPortProp->createView(), 'propositor' => $propositor, 'portfolios' => $portfolios));
    }

    function gerarFormPropositor($nomeFormulario){
      $formularioTbClass = $this->get('form.factory')
              ->createNamedBuilder($nomeFormulario, FormType::class)
              ->add('IdProposer', HiddenType::class, array('label' => false))
              ->add('IdPortfolios', HiddenType::class, array('label' => false))
              ->getForm();
      return $formularioTbClass;

    }

    function selecionarPropositorSalvo(){
        $idClass = $this->get('session')->get('idTurmaEdicao');

        $turma = $this->getDoctrine()
                ->getRepository('AppBundle:TbClass')
                ->findOneBy(array('idClass' => $idClass));
        $this->logControle->logAdmin("Dados da turma  : " . print_r($turma, true));

        $dadosPropositor = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findOneBy(array('idUser' => $turma->getIdProposer()));
        $this->logControle->logAdmin("Dados do Propositor : " . print_r($dadosPropositor, true));

        if (count($dadosPropositor) > 0) {
          $propositor[] = array(
              'idProposer' => $dadosPropositor->getIdUser(),
              'nmProposer' => $dadosPropositor->getNmUser()
          );
        }else{
          $propositor[] = null;
        }

        $this->logControle->logAdmin("Dados do Propositor return : " . print_r($propositor, true));
        return $propositor;
    }

    function selecionarPortfoliosSalvos(){

        $dadosPortfolios = $this->selecionarPortfoliosTurma();

        $this->logControle->logAdmin("Portfólios da turma : " . print_r($dadosPortfolios, true));

        if (count($dadosPortfolios) > 0) {
          foreach ($dadosPortfolios as $p) {
            $portfolios[] = array(
                'idPortfolio' => $p['idPortfolio']['idPortfolio'],
                'nmPortfolio' => $p['idPortfolio']['dsTitle']
            );
          }
        }else{
          $portfolios[] = null;
        }

        return $portfolios;
    }

    function selecionarPortfoliosTurma(){
      $idClass = $this->get('session')->get('idTurmaEdicao');

      $queryBuilderPortClass = $this->em->createQueryBuilder();
      $queryBuilderPortClass
              ->select('pc,p')
              ->from('AppBundle:TbPortfolioClass', "pc")
              ->innerJoin('pc.idPortfolio', 'p', 'WITH', 'p.idPortfolio= pc.idPortfolio')
              ->where($queryBuilderPortClass->expr()->eq('pc.idClass', $idClass))
              ->getQuery()
              ->execute();
      $portfoliosTurma = $queryBuilderPortClass->getQuery()->getArrayResult();

      return $portfoliosTurma;
    }

    function editarPortProp($dadosForm){
      $idClass = $this->get('session')->get('idTurmaEdicao');

      $classEditar = $this->getDoctrine()
              ->getRepository('AppBundle:TbClass')
              ->findOneBy(array('idClass' => $idClass));

      PropositorController::persistirObjetoTurma($classEditar, $dadosForm);
      PropositorController::persistirObjetoPortfolio($classEditar, $dadosForm);
    }

    function persistirObjetoTurma($classEditar, $dadosForm) {
        $this->em = $this->getDoctrine()->getManager();

        $propositor = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findOneBy(array('idUser' => $dadosForm['IdProposer']));

        $classEditar->setIdProposer($propositor);

        $this->em->persist($classEditar);

        $this->em->flush();
    }

    function persistirObjetoPortfolio($classEditar, $dadosForm) {
        $this->em = $this->getDoctrine()->getManager();

        $idClass = $this->get('session')->get('idTurmaEdicao');

        $queryBuilderPortClass = $this->em->createQueryBuilder();
        $queryBuilderPortClass
                ->select('pc,p')
                ->from('AppBundle:TbPortfolioClass', "pc")
                ->innerJoin('pc.idPortfolio', 'p', 'WITH', 'p.idPortfolio= pc.idPortfolio')
                ->where($queryBuilderPortClass->expr()->eq('pc.idClass', $idClass))
                ->getQuery()
                ->execute();
        $portfoliosTurma = $queryBuilderPortClass->getQuery()->getArrayResult();

        $portfoliosTurmaForm = explode(";", $dadosForm['IdPortfolios']);
        // $portfoliosTurmaForm =$dadosForm['IdPortfolios'];


        for ($i=0; $i < sizeof($portfoliosTurmaForm); $i++) {

          $flag = false;
          foreach ($portfoliosTurma as $pt) {
            $this->logControle->logAdmin("PT  : " . print_r($pt, true));
            if($portfoliosTurmaForm[$i]== $pt['idPortfolio']){
              $flag = true;
            }
          }

          if($flag == false){
            $novoPortClass = new TbPortfolioClass();
            $turma = $this->getDoctrine()
                        ->getRepository('AppBundle:TbClass')
                        ->findOneBy(array('idClass' => $idClass));

            $portfolio = $this->getDoctrine()
                        ->getRepository('AppBundle:TbPortfolio')
                        ->findOneBy(array('idPortfolio' => $portfoliosTurmaForm[$i]));

            $novoPortClass->setIdClass($turma);
            $novoPortClass->setIdPortfolio($portfolio);

            $this->em->persist($novoPortClass);
            // $idPortfolio = $objetoPortfolio->getIdPortfolio();

            $this->em->flush();
          }
        }

        $this->em->flush();
    }
}
