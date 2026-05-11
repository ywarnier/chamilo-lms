<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrackEExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEExercise::class);
    }

    /**
     * Returns the best score percentage (0–100) per quiz for the given user.
     * Only considers completed attempts (status != 'incomplete').
     * Result: array of ['quiz' => CQuiz, 'bestPct' => float].
     *
     * @param CQuiz[] $quizzes
     *
     * @return array<int, array{quiz: CQuiz, bestPct: float}>
     */
    public function findBestPercentagePerQuiz(User $user, array $quizzes): array
    {
        if ([] === $quizzes) {
            return [];
        }

        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.quiz) AS quizId, MAX(t.score / t.maxScore * 100) AS bestPct')
            ->where('t.user = :user')
            ->andWhere('t.quiz IN (:quizzes)')
            ->andWhere("t.status != 'incomplete'")
            ->andWhere('t.maxScore > 0')
            ->groupBy('t.quiz')
            ->setParameter('user', $user)
            ->setParameter('quizzes', $quizzes)
            ->getQuery()
            ->getResult()
        ;

        $quizIndex = [];
        foreach ($quizzes as $quiz) {
            $quizIndex[$quiz->getIid()] = $quiz;
        }

        $result = [];
        foreach ($rows as $row) {
            $quizId = (int) $row['quizId'];
            if (isset($quizIndex[$quizId])) {
                $result[] = ['quiz' => $quizIndex[$quizId], 'bestPct' => (float) $row['bestPct']];
            }
        }

        return $result;
    }

    public function delete(TrackEExercise $track): void
    {
        $this->getEntityManager()->remove($track);
        $this->getEntityManager()->flush();
    }

    /**
     * Get exercises with pending corrections grouped by exercise ID.
     */
    public function getPendingCorrectionsByExercise(int $courseId, ?int $sessionId): array
    {
        $qb = $this->createQueryBuilder('te');

        $qb->select('IDENTITY(te.quiz) AS exerciseId, COUNT(te.exeId) AS pendingCount')
            ->where('te.status = :status')
            ->andWhere('te.course = :courseId')
        ;
        if (!empty($sessionId)) {
            $qb->andWhere('te.session = :sessionId')
                ->setParameter('sessionId', $sessionId)
            ;
        } else {
            $qb->andWhere('te.session IS NULL');
        }
        $qb->setParameter('status', 'incomplete')
            ->setParameter('courseId', $courseId)
            ->groupBy('te.quiz')
        ;

        return $qb->getQuery()->getResult();
    }
}
