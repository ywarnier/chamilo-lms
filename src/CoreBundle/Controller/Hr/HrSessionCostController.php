<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
class HrSessionCostController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExtraFieldRepository $extraFieldRepo,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
    ) {}

    #[Route('/api/hr/sessions/{sessionId}/cost', methods: ['GET'])]
    public function getCost(int $sessionId): JsonResponse
    {
        $efv = $this->extraFieldValuesRepo->getValueByVariableAndItem(
            'cost',
            $sessionId,
            ExtraField::SESSION_FIELD_TYPE
        );

        return $this->json([
            'sessionId' => $sessionId,
            'cost' => (float) ($efv?->getFieldValue() ?? 0),
        ]);
    }

    #[Route('/api/hr/sessions/{sessionId}/cost', methods: ['POST'])]
    public function setCost(int $sessionId, Request $request): JsonResponse
    {
        $session = $this->em->find(Session::class, $sessionId);

        if (!$session instanceof Session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $cost = isset($data['cost']) ? (float) $data['cost'] : 0.0;

        if ($cost < 0) {
            return $this->json(['error' => 'Cost must be non-negative'], Response::HTTP_BAD_REQUEST);
        }

        $extraField = $this->extraFieldRepo->findByVariable(ExtraField::SESSION_FIELD_TYPE, 'cost');

        if (!$extraField instanceof ExtraField) {
            return $this->json(['error' => 'Cost extra field not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $efv = $this->extraFieldValuesRepo->getValueByVariableAndItem(
            'cost',
            $sessionId,
            ExtraField::SESSION_FIELD_TYPE
        );

        if (null === $efv) {
            $efv = (new ExtraFieldValues())
                ->setField($extraField)
                ->setItemId($sessionId)
                ->setFieldValue((string) $cost)
            ;
            $this->em->persist($efv);
        } else {
            $efv->setFieldValue((string) $cost);
        }

        $this->em->flush();

        return $this->json([
            'sessionId' => $sessionId,
            'cost' => $cost,
        ]);
    }
}
