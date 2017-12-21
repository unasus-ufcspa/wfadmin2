<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\UsuarioController;
use AppBundle\Entity\TbActivity;
use AppBundle\Entity\TbPortfolio;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class PortfoliosController extends Controller {

    public $error;
    public $logControle;
    public $em;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/portfolios")
     */
    function portfolios(Request $request) {
        if (!$this->get('session')->get('idUser')) {

            return $this->redirectToRoute('login');
        } else {
            $this->em = $this->getDoctrine()->getManager();

            $arrayPortfolios = $this->gerarArrayPortfolios();

            $this->formAdicionarPortfolio = PortfoliosController::gerarFormularioPortfolio("adicionarPort");
            $this->formAdicionarPortfolio->handleRequest($request);

            if ($request->request->has($this->formAdicionarPortfolio->getName())) {
                if ($this->formAdicionarPortfolio->isSubmitted() && $this->formAdicionarPortfolio->isValid()) {
                    $dadosFormAdicionarPortfolio = $this->formAdicionarPortfolio->getData();
                    $this->adicionarPortfolio($dadosFormAdicionarPortfolio);
                    return $this->redirectToRoute('portfolios');
                }
            }

            return $this->render('portfolios.html.twig', array('portfolios' => $arrayPortfolios,  'formPort' => $this->formAdicionarPortfolio->createView()));
        }
    }

    public function gerarArrayPortfolios() {
        $queryBuilderClass = $this->em->createQueryBuilder();
        $queryBuilderClass
                ->select('p')
                ->from('AppBundle:TbPortfolio', 'p')
                ->getQuery()
                ->execute();
        $portfolios = $queryBuilderClass->getQuery()->getArrayResult();
        foreach ($portfolios as $portfolio) {

            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('count(a.idActivity)')
                    ->from('AppBundle:TbActivity', 'a')
                    ->where($queryBuilder->expr()->eq('a.idPortfolio', $portfolio['idPortfolio']))
                    ->getQuery()
                    ->execute();
            $portfolio['nmAtividades'] = $queryBuilder->getQuery()->getSingleScalarResult();

            $arrayPortfolios[] = array(
              'idPortfolio' => $portfolio['idPortfolio'],
              'dsTitle' => $portfolio['dsTitle'],
              'nmAtividades' => $portfolio['nmAtividades']
            );
        }
        return $arrayPortfolios;
    }

    /**
     * @Route("/cadastroPortfolio/{idPortfolio}")
     */
    function cadastroPortfolio(Request $request, $idPortfolio) {
        if (!$this->get('session')->get('idUser')) {

            return $this->redirectToRoute('login');
        } else {
            $this->em = $this->getDoctrine()->getManager();

            $dadosPortfolio = array();
            $arrayAtividades = array();
            if ($idPortfolio > -1) {
              $dadosPortfolio = $this->carregarDadosPortfolio($idPortfolio);
              $arrayAtividades= $this->carregarAtividadesPortfolio($idPortfolio);
            }else{
              $dadosPortfolio = array(
                  'dsTitle' => null,
                  'dsDescription' => null
              );
              $arrayAtividades  = array();
            }

            $this->formEditarPortfolio = PortfoliosController::gerarFormularioPortfolio("editarPort");
            $this->formEditarPortfolio->handleRequest($request);

            $this->formAdicionarAtividade = PortfoliosController::gerarFormularioAddAtividade("adicionarAtiv");
            $this->formAdicionarAtividade->handleRequest($request);

            $this->formEditarAtividade = PortfoliosController::gerarFormularioAddAtividade("editarAtiv");
            $this->formEditarAtividade->handleRequest($request);

            if ($request->request->has($this->formEditarPortfolio->getName())) {
                if ($this->formEditarPortfolio->isSubmitted() && $this->formEditarPortfolio->isValid()) {
                    $dadosFormEditarPortfolio = $this->formEditarPortfolio->getData();
                    $this->editarPortfolio($dadosFormEditarPortfolio, $idPortfolio);
                    // return $this->redirectToRoute('cadastroPortfolio/'+$idPortfolio);
                    header("Refresh:0");
                }
            }
            if ($request->request->has($this->formAdicionarAtividade->getName())) {
                if ($this->formAdicionarAtividade->isSubmitted() && $this->formAdicionarAtividade->isValid()) {
                    $dadosFormAdicionarAtividade = $this->formAdicionarAtividade->getData();
                    $this->adicionarAtividade($dadosFormAdicionarAtividade, $idPortfolio);
                    // return $this->redirectToRoute('cadastroPortfolio/'+$idPortfolio);
                    header("Refresh:0");
                }
            }
            if ($request->request->has($this->formEditarAtividade->getName())) {
                if ($this->formEditarAtividade->isSubmitted() && $this->formEditarAtividade->isValid()) {
                    $dadosFormEditarAtividade = $this->formEditarAtividade->getData();
                    $this->editarAtividade($dadosFormEditarAtividade, $idPortfolio);
                    // return $this->redirectToRoute('cadastroPortfolio/'+$idPortfolio);
                    header("Refresh:0");
                }
            }

            return $this->render('cadastroPortfolio.html.twig', array('atividades' => $arrayAtividades, 'portfolio' => $dadosPortfolio, 'formPort' => $this->formEditarPortfolio->createView(), 'formAtiv' => $this->formAdicionarAtividade->createView(), 'editAtiv' => $this->formEditarAtividade->createView()));
        }
    }

    function carregarDadosPortfolio($idPortfolio) {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('p')
                ->from('AppBundle:TbPortfolio', 'p')
                ->where($queryBuilder->expr()->eq('p.idPortfolio', $idPortfolio))
                ->getQuery()
                ->execute();
        $portfolio = $queryBuilder->getQuery()->getArrayResult();

        foreach ($portfolio as $arrayPortfolios) {
            $dadosPortfolio = array(
                'id' => $idPortfolio,
                'dsTitle' => $arrayPortfolios['dsTitle'],
                'dsDescription' => $arrayPortfolios['dsDescription']
            );
        }
        return $dadosPortfolio;
    }

    public function carregarAtividadesPortfolio($idPortfolio) {
        $queryBuilderPort = $this->em->createQueryBuilder();
        $queryBuilderPort
                ->select('a')
                ->from('AppBundle:TbActivity', 'a')
                ->where($queryBuilderPort->expr()->eq('a.idPortfolio', $idPortfolio))
                ->getQuery()
                ->execute();
        $atividades = $queryBuilderPort->getQuery()->getArrayResult();
        $dadosAtividades=null;
        foreach ($atividades as $atividade) {
            $dadosAtividades[] = array(
                'id' => $atividade['idActivity'],
                'dsTitle' => $atividade['dsTitle'],
                'dsDescription' => $atividade['dsDescription'],
                'nuOrder' => $atividade['nuOrder']
            );
        }
        return $dadosAtividades;
    }

    function gerarFormularioPortfolio($nomeFormulario) {
        $formularioTbPortfolio = $this->get('form.factory')
                ->createNamedBuilder($nomeFormulario, FormType::class)
                ->add('DsTitle', TextType::class, array('label' => false))
                ->add('DsDescription', TextareaType ::class, array('label' => false))
                ->getForm();
        return $formularioTbPortfolio;
    }

    function adicionarPortfolio($dadosFormAdicionarPortfolio) {
        $novoPortfolio = new TbPortfolio();
        $this->persistirObjetoPortfolio($novoPortfolio, $dadosFormAdicionarPortfolio);
    }

    function editarPortfolio($dadosFormEditar, $idPortfolio){
      $portfolio = $this->getDoctrine()
              ->getRepository('AppBundle:TbPortfolio')
              ->findOneBy(array('idPortfolio' => $idPortfolio));

      PortfoliosController::persistirObjetoPortfolio($portfolio, $dadosFormEditar);
    }

    function persistirObjetoPortfolio($objetoPortfolio, $dadosPortfolio) {
        $this->em = $this->getDoctrine()->getManager();
        $objetoPortfolio->setDsTitle($dadosPortfolio['DsTitle']);
        $objetoPortfolio->setDsDescription($dadosPortfolio['DsDescription']);

        $this->em->persist($objetoPortfolio);
        $idPortfolio = $objetoPortfolio->getIdPortfolio();

        $this->em->flush();

        return $idPortfolio;
    }

    function gerarFormularioAddAtividade($nomeFormulario) {

        $formularioTbActivity = $this->get('form.factory')
                ->createNamedBuilder($nomeFormulario, FormType::class)
                ->add('IdActivity', HiddenType::class, array('label' => false))
                ->add('DsTitle', TextType::class, array('label' => false))
                ->add('DsDescription', TextareaType ::class, array('label' => false))
                ->getForm();
        return $formularioTbActivity;
    }

    function adicionarAtividade($dadosFormAdicionarAtividade, $idPortfolio) {
        $novaAtividade = new TbActivity();
        $this->persistirObjetoAtividade($novaAtividade, $dadosFormAdicionarAtividade, $idPortfolio);
    }

    function editarAtividade($dadosFormEditar, $idPortfolio){
      $atividade = $this->getDoctrine()
              ->getRepository('AppBundle:TbActivity')
              ->findOneBy(array('idActivity' => $dadosFormEditar['IdActivity']));

      PortfoliosController::persistirObjetoAtividade($atividade, $dadosFormEditar, $idPortfolio);
    }

    function persistirObjetoAtividade($objetoAtividade, $dadosAtividade, $idPortfolio) {
        $this->em = $this->getDoctrine()->getManager();

        $objetoPort = $this->em->getRepository('AppBundle:TbPortfolio')
                ->findOneBy(array('idPortfolio' => $idPortfolio));
        $objetoAtividade->setIdPortfolio($objetoPort);
        $objetoAtividade->setDsTitle($dadosAtividade['DsTitle']);
        $objetoAtividade->setDsDescription($dadosAtividade['DsDescription']);

        $this->em->persist($objetoAtividade);

        $this->em->flush();
    }
}
