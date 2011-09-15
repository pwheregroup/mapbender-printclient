<?php

namespace Mapbender\WmsBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\WmsBundle\Entity\WMSService;
use Mapbender\WmsBundle\Entity\WMSLayer;
use Mapbender\WmsBundle\Entity\GroupLayer;
use Mapbender\WmsBundle\Component\CapabilitiesParser;
use Mapbender\WmsBundle\Form\WMSType;

/*
* @package bkg
* @author Karim Malhas <karim@malhas.de>
*/
class WMSController extends Controller {
    
    /**
     * Shows the startpage of the WMS Bundle
     * @Route("/")
     * @Method({"GET"})
     * @Template()
     * @ParamConverter("wmsList",class="Mapbender\WmsBundle\Entity\WMSService")
    */
    public function indexAction(array $wmsList){

        $request = $this->get('request');
        $offset = $request->get('usedOffset');
        $limit = $request->get('usedLimit');
        $nextOffset = count($wmsList) < $limit ? $offset : $offset + $limit;
        $prevOffset = ($offset - $limit)  > 0 ? $offset - $limit : 0;
        return array(
            "wmsList" => $wmsList,
            "nextOffset" =>  $nextOffset,
            "prevOffset" => $prevOffset,
            "limit" => $limit
        );
    }


    /**
     * shows the dialog that allows adding a WMS
     * @Route("/add")
     * @Method({"GET"})
     * @Template()
    */
    public function registerAction(){
        return array();
    }
    
    /**
     * shows preview of WMS
     * @Route("/preview")
     * @Method({"POST"})
     * @Template()
    */
    public function previewAction(){
        $getcapa_url = $this->get('request')->request->get('getcapa_url');
        if(!$getcapa_url){
            throw new \Exception('getcapa_url not set');
        }
        

        $ch = curl_init($getcapa_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        try {
            $proxyConf = $this->container->getParameter('proxy');
        }catch(\InvalidArgumentException $E){
            // thrown when the parameter is not set
            // maybe some logging ?
            $proxyConf = array();
        }
        if($proxyConf && isset($proxyConf['host']) && $proxyConf['host'] != ""){
            curl_setopt($ch, CURLOPT_PROXY,$proxyConf['host']);
            curl_setopt($ch, CURLOPT_PROXYPORT,$proxyConf['port']?:"");
        }

        $data = curl_exec($ch);

        if(!$data){
            $this->get("logger")->debug("$getcapa_url returned no data");
            throw new \Exception('Service returned no Data');
        }

        $capaParser = new CapabilitiesParser($data);

        $wms = $capaParser->getWMSService();
        if(!$wms){
            throw new \Exception("could not parse data for url '$getcapa_url'");
        }
    

        $form = $this->get('form.factory')->create(new WMSType(), $wms,array(
            "exceptionFormats" => $wms->getExceptionFormats(),
            "requestGetCapabilitiesFormats" => $wms->getRequestGetCapabilitiesFormats(),
            "requestGetMapFormats" => $wms->getRequestGetMapFormats(),
            "requestGetFeatureInfoFormats" => $wms->getRequestGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $wms->getRequestDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $wms->getRequestGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $wms->getRequestGetStylesFormats(),
            "requestPutStylesFormats" => $wms->getRequestPutStylesFormats(),
        ));
        
        return array(
                "getcapa_url"=>$getcapa_url,
                "wms" => $wms,
                "form" => $form->createView(),
                "xml" => $data
            );
    }

    /**
     * adds a WMS
     * @Route("/")
     * @Method({"POST"})
    */
    public function addAction(){

        $request = $this->get('request');

        $requestWMS = $request->get('WMSService');
        $wms = new WMSService();
        $wms = $this->buildWMSFormStructure($wms,$requestWMS);
        $form = $this->get('form.factory')->create(new WMSType(),$wms); 
        $form->bindRequest($request);
    
        if($form->isValid()){
            $em = $this->get("doctrine.orm.entity_manager");
            $this->persistRecursive($wms,$em);
            $em->persist($wms);
            $em->flush();
            $this->get('session')->setFlash('info',"WMS Added");
            return $this->redirect($this->generateUrl("mapbender_wms_wms_index",array(), true));
        }else{
            // FIXME: getcapa_url is missing, xml is missing
            $this->get('session')->setFlash('error',"Could not Add WMS");
            return $this->render("MapbenderWmsBundle:WMS:preview.html.twig",array(
                    "getcapa_url"=> "",
                    "wms" => $wms,
                    "form" => $form->createView(),
                    "xml" =>""
                ));
        }
    
        
    }

    /**
     * Shows the WMS in an Editor
     * @Route("/{wmsId}"))
     * @Method({"GET"})
     * @Template()
    */
    public function editAction(WMSService $wms){
        $form = $this->get('form.factory')->create(new WMSType(),$wms); 
        return array(
            "wms" => $wms,
            "form"  => $form->createView(),
        );
    }
    
    /**
     * Shows the WMS in an Editor
     * @Route("/{wmsId}"))
     * @Method({"POST"})
     * @Template()
    */
    public function saveAction(WMSService $wms){
        $request = $this->get('request');
        /* build up nested wmslayer structure */
        $requestWMS = $request->get('WMSService');
        $form = $this->get('form.factory')->create(new WMSType(),$wms); 
        $form->bindRequest($request);
        $em = $this->get("doctrine.orm.entity_manager");
        $this->persistRecursive($wms,$em);
        // FIXME: error handling
        $this->get('session')->setFlash('info',"WMS Saved");
        return $this->redirect($this->generateUrl("mapbender_wms_wms_edit", array("wmsId"=>$wms->getId())));
    }

    /**
     * shows the dialog for wms Deletion confirmation
     * @Route("/{wmsId}/delete")
     * @Method({"GET"})
     * @Template()
    */
    public function confirmdeleteAction(WMSService $wms){
        return array(
               'wms' => $wms 
        );
    }

    /**
     * deletes a WMS
     * @Route("/{wmsId}/delete")
     * @Method({"POST"})
    */
    public function deleteAction(WMSService $wms){
        // TODO: check wether a layer is used by a VWMS still
        $em = $this->getDoctrine()->getEntityManager();
        $this->removeRecursive($wms,$em);
        $em->remove($wms);
        $em->flush();
        //FIXME: error handling
        $this->get('session')->setFlash('info',"WMS deleted");
        return $this->redirect($this->generateUrl("mapbender_wms_wms_index"));
    }

 
    /**
     * Recursively persists a nested Layerstructure
     * param GroupLayer
     * param EntityManager
    */
    public function persistRecursive($grouplayer,$em){
        $em->persist($grouplayer);
        if(count($grouplayer->getLayer()) > 0 ){
            foreach($grouplayer->getLayer() as $layer){
                $layer->setParent($grouplayer);
                $this->persistRecursive($layer,$em);
            }
        }
        $em->flush();
    }
    /**
     * Recursively remove a nested Layerstructure
     * param GroupLayer
     * param EntityManager
    */
    public function removeRecursive($grouplayer,$em){
        foreach($grouplayer->getLayer() as $layer){
            $this->removeRecursive($layer,$em);
        }
        $em->flush();
        $em->remove($grouplayer);
    }

    /**
     *  
     * Takes an Arraystructure from a POSTrequest and recurses into the nested layers to build a matching WMSLayer structure
     * So that a Form can be bound to the layer
     * param GroupLayer the rootlayer of the Layer hierarchy
     * param array POST request from a WMS structure
    */
    public function buildWMSFormStructure($grouplayer,array $grouplayerArr){
        if(isset($grouplayerArr['layer']) && is_array($grouplayerArr['layer'])){
                foreach($grouplayerArr['layer'] as $layerArr){
                        $layer = new WMSLayer();
                        if(isset($layerArr['layer']) && is_array($layerArr['layer']) && count($layerArr['layer'])){
                                $layer = $this->buildWMSFormStructure($layer, $layerArr);

                        }   
                        $grouplayer->addLayer($layer);
                }   
        }   
        return $grouplayer;
    } 

}