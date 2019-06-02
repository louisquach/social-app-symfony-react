<?php

namespace App\Controller;

use App\Core\Doctrine\Pagination;
use App\Entity\Post;
use App\Entity\User;
use App\Service\Validator;
use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator as v;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Class PostController
 * @package App\Controller
 */
class PostController extends BaseController
{
    /**
     * Create post
     *
     * @Route("/post", methods={"POST"})
     *
     * @param Validator $validator
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \App\Exception\HttpBadRequestException
     * @throws \App\Exception\HttpConflictException
     * @throws \App\Exception\ValidationException
     *
     * @SWG\Response(
     *     response=200,
     *     description="Create Post"
     * )
     * @SWG\Tag(name="Post")
     *
     */
    public function create(Validator $validator)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $requestData = $this->getRequestContent();

        $validator
            ->setValidator(
                v::oneOf(
                    v::notEmpty()->noWhitespace()->stringType(),
                    v::nullType()
                ),
                'content',
                'content must be a string or null type'
            )
            ->validate($requestData);

        $identity = $this->getIdentity();

        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->find($identity->getId());

        $post = $this->deserialize($requestData, Post::class, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => Post::GUARDED_FIELDS
        ]);

        $post->setUser($user);

        $em->persist($post);
        $em->flush();

        return $this->setResponse('Post created successfully');
    }

    /**
     * Get post
     *
     * @Route("/post", methods={"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \App\Exception\HttpBadRequestException
     * @throws \App\Exception\HttpConflictException
     * @throws \App\Exception\ValidationException
     *
     * @SWG\Response(
     *     response=200,
     *     description="Get Post"
     * )
     * @SWG\Tag(name="Post")
     *
     */
    public function postList(Request $request)
    {
        $criteria = $request->get('criteria') ? $request->get('criteria') : [];

        $criteria = array_merge_recursive($criteria, [
            'order' => ['creationDate DESC']
        ]);

        $serializer = ($request->get('serializer', null))
            ?: [
                'body', 'id', 'creationDate', 'user' => ['id', 'fullName']
            ];

        $limit = ($request->get('limitPerPage') && intval($request->get('limitPerPage')) < 10)
            ? intval($request->get('limitPerPage'))
            : 10;

        $pagination = new Pagination(
            $limit,
            (($request->get('page')) ? intval($request->get('page')) : 1)
        );

        $paginatedData = $this->getDoctrine()->getManager()
            ->getRepository(Post::class)
            ->getPostList($criteria, $pagination);

        $response = [
            'data' => $paginatedData['result'],
            'page' => $paginatedData['currentPage'],
            'count' => $paginatedData['count'],
            'offset' => $paginatedData['offset'],
            'limitPerPage' => $paginatedData['limitPerPage'],
            'criteria' => $criteria
        ];

        return $this->setResponse($response, 200, [], [
            AbstractNormalizer::ATTRIBUTES => $serializer,
            AbstractNormalizer::IGNORED_ATTRIBUTES => Post::HIDDEN_FIELDS
        ]);
    }
}