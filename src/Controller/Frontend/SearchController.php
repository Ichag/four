<?php

declare(strict_types=1);

namespace Bolt\Controller\Frontend;

use Bolt\Content\ContentType;
use Bolt\Controller\BaseController;
use Bolt\Entity\Content;
use Bolt\Repository\ContentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends BaseController
{
    /**
     * @Route("/search", methods={"GET", "POST"}, name="search")
     *
     * @param ContentRepository $content
     * @param Request           $request
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     *
     * @return Response
     */
    public function search(ContentRepository $content, Request $request): Response
    {
        $page = (int) $request->query->get('page', 1);

        /** @var Content $records */
        $records = $content->findAll($page);

        $contenttype = ContentType::factory($contenttypeslug, $this->config->get('contenttypes'));

        $templates = $this->templateChooser->listing($contenttype);

        return $this->renderTemplate($templates, ['records' => $records]);
    }
}
