<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Job controller.
 *
 */
class JobController extends Controller
{
    /**
     * Lists all Job entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
 
        $categories = $em->getRepository('EnsJobeetBundle:Category')->getWithJobs();
     
        foreach($categories as $category)
        {
            $category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')->getActiveJobs($category->getId(), $this->container->getParameter('max_jobs_on_homepage')));
            $category->setMoreJobs($em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId()) - $this->container->getParameter('max_jobs_on_homepage'));
        }
     
        return $this->render('EnsJobeetBundle:Job:index.html.twig', array(
        'categories' => $categories
        ));
    }

    /**
     * Creates a new Job entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Job();
        //$request = $this->getRequest();
        $form = $this->createForm(JobType::class, $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
         
            $em->persist($entity);
            $em->flush();
         
            return $this->redirect($this->generateUrl('ens_job_preview', array(
              'company' => $entity->getCompanySlug(),
              'location' => $entity->getLocationSlug(),
              'token' => $entity->getToken(),
              'position' => $entity->getPositionSlug()
            )));
        }

        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    public function newAction()
    {
      $entity = new Job();
      $entity->setType('full-time');
      $form   = $this->createForm(JobType::class, $entity);
     
      return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
        'entity' => $entity,
        'form'   => $form->createView()
      ));
    }

    /**
     * Finds and displays a Job entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
     
        $entity = $em->getRepository('EnsJobeetBundle:Job')->getActiveJob($id);

        //die(var_dump($entity));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }


        $deleteForm = $this->createDeleteForm($id);

        //die(var_dump($deleteForm));
     
        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
     
        ));
    }

    /**
     * Displays a form to edit an existing Job entity.
     *
     */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getEntityManager();
 
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
         
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        if ($entity->getIsActivated()) {
            throw $this->createNotFoundException('Job is activated and cannot be edited.');
        }

        $deleteForm = $this->createDeleteForm($token);
        $editForm = $this->createForm(JobType::class, $entity);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function updateAction($token)
    {
      $em = $this->getDoctrine()->getEntityManager();
     
      $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
     
      if (!$entity) {
        throw $this->createNotFoundException('Unable to find Job entity.');
      }
     
      $editForm   = $this->createForm(JobType::class, $entity);
      $deleteForm = $this->createDeleteForm($token);
     
      $request = $this->getRequest();
     
      $editForm->handleRequest($request);
     
      if ($editForm->isValid()) {
        $em->persist($entity);
        $em->flush();
     
        return $this->redirect($this->generateUrl('ens_job_preview', array(
          'company' => $entity->getCompanySlug(),
          'location' => $entity->getLocationSlug(),
          'token' => $entity->getToken(),
          'position' => $entity->getPositionSlug()
        )));
      }
     
      return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
        'entity'      => $entity,
        'edit_form'   => $editForm->createView(),
        'delete_form' => $deleteForm->createView(),
      ));
    }

    /**
     * Deletes a Job entity.
     *
     */
    public function deleteAction($token)
    {
      $form = $this->createDeleteForm($token);
      $request = $this->getRequest();
     
      $form->handleRequest($request);
     
      if ($form->isValid()) {
        $em = $this->getDoctrine()->getEntityManager();
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
     
        if (!$entity) {
          throw $this->createNotFoundException('Unable to find Job entity.');
        }
     
        $em->remove($entity);
        $em->flush();
      }
     
      return $this->redirect($this->generateUrl('ens_job'));
    }

    /**
     * Creates a form to delete a Job entity.
     *
     * @param Job $job The Job entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($token)
    {
      return $this->createFormBuilder(array('token' => $token))
        ->add('token', HiddenType::class)
        ->getForm()
      ;
    }

    public function previewAction($token)
    {
      $em = $this->getDoctrine()->getEntityManager();
     
      $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
     
      if (!$entity) {
        throw $this->createNotFoundException('Unable to find Job entity.');
      }
     
        $deleteForm = $this->createDeleteForm($entity->getId());
        $publishForm = $this->createPublishForm($entity->getToken());
        $extendForm = $this->createExtendForm($entity->getToken());
     
        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'publish_form' => $publishForm->createView(),
            'extend_form' => $extendForm->createView(),
        ));
    }

    public function publishAction($token)
    {
      $form = $this->createPublishForm($token);
      $request = $this->getRequest();
     
      $form->handleRequest($request);
     
      if ($form->isValid()) {
        $em = $this->getDoctrine()->getEntityManager();
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
     
        if (!$entity) {
          throw $this->createNotFoundException('Unable to find Job entity.');
        }
     
        $entity->publish();
        $em->persist($entity);
        $em->flush();
     
        $this->get('session')->getFlashBag()->add('notice', 'Your job is now online for 30 days.');
      }
     
      return $this->redirect($this->generateUrl('ens_job_preview', array(
        'company' => $entity->getCompanySlug(),
        'location' => $entity->getLocationSlug(),
        'token' => $entity->getToken(),
        'position' => $entity->getPositionSlug()
      )));
    }

    private function createPublishForm($token)
    {
      return $this->createFormBuilder(array('token' => $token))
        ->add('token', HiddenType::class)
        ->getForm()
      ;
    }

    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    public function extendAction($token)
    {
      $form = $this->createExtendForm($token);
      $request = $this->getRequest();
     
      $form->handleRequest($request);
     
      if ($form->isValid()) {
        $em = $this->getDoctrine()->getEntityManager();
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
     
        if (!$entity) {
          throw $this->createNotFoundException('Unable to find Job entity.');
        }
     
        if (!$entity->extend()) {
          throw $this->createNotFoundException('Unable to find extend the Job.');
        }
     
        $em->persist($entity);
        $em->flush();
     
        $this->get('session')->getFlashBag()->add('notice', sprintf('Your job validity has been extended until %s.', $entity->getExpiresAt()->format('m/d/Y')));
      }
     
      return $this->redirect($this->generateUrl('ens_job_preview', array(
        'company' => $entity->getCompanySlug(),
        'location' => $entity->getLocationSlug(),
        'token' => $entity->getToken(),
        'position' => $entity->getPositionSlug()
      )));
    }

    private function createExtendForm($token)
    {
      return $this->createFormBuilder(array('token' => $token))
        ->add('token', HiddenType::class)
        ->getForm()
      ;
    }
}
