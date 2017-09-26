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

            return $this->render('portfolios.html.twig', array('portfolios' => $arrayPortfolios));
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
     * @Route("/cadastroPortfolio")
     */
    function cadastroPortfolio(Request $request) {
        if (!$this->get('session')->get('idUser')) {

            return $this->redirectToRoute('login');
        } else {
            $this->em = $this->getDoctrine()->getManager();

            return $this->render('cadastroPortfolio.html.twig');
        }
    }
}
