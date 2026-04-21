<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\BenefitAssignment;
use Chamilo\CoreBundle\Entity\Compensation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class BenefitAssignmentTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function createCompensation(string $title): Compensation
    {
        $em = $this->getEntityManager();
        $compensation = (new Compensation())
            ->setTitle($title)
            ->setScore(5.0)
            ->setAuthor($this->getAdmin())
        ;
        $em->persist($compensation);
        $em->flush();

        return $compensation;
    }

    private function createAssignment(User $user, Compensation $compensation, ?string $comment = null): BenefitAssignment
    {
        $em = $this->getEntityManager();
        $assignment = (new BenefitAssignment())
            ->setUser($user)
            ->setCompensation($compensation)
            ->setAssignmentDatetime(new DateTime('2025-01-01 09:00:00'))
            ->setAssignmentAuthor($this->getAdmin())
            ->setComment($comment)
        ;
        $em->persist($assignment);
        $em->flush();

        return $assignment;
    }

    public function testGetCollectionAsAdminReturns200(): void
    {
        $token = $this->getUserToken();

        $response = $this->createClientWithCredentials($token)->request('GET', '/api/benefit_assignments');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/BenefitAssignment',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetCollectionAsHrUserReturns200(): void
    {
        $hr = $this->createUser('ba_hr_coll', 'ba_hr_coll', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $response = $this->createClientWithCredentials($token)->request('GET', '/api/benefit_assignments');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCollectionUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/benefit_assignments');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCollectionAsStudentReturns403(): void
    {
        $student = $this->createUser('ba_student_coll');
        $token = $this->getUserTokenFromUser($student);

        $this->createClientWithCredentials($token)->request('GET', '/api/benefit_assignments');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetSingleAsAdminReturns200(): void
    {
        $compensation = $this->createCompensation('BA Single Admin Comp');
        $employee = $this->createUser('ba_employee_single');
        $assignment = $this->createAssignment($employee, $compensation, 'Test comment');
        $token = $this->getUserToken();

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/benefit_assignments/'.$assignment->getId()
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/BenefitAssignment',
            '@type' => 'BenefitAssignment',
            'comment' => 'Test comment',
        ]);
    }

    public function testGetSingleAsOwnerReturns200(): void
    {
        $compensation = $this->createCompensation('BA Single Owner Comp');
        $owner = $this->createUser('ba_owner_single');
        $assignment = $this->createAssignment($owner, $compensation);
        $token = $this->getUserTokenFromUser($owner);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/benefit_assignments/'.$assignment->getId()
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetSingleForbiddenForOtherUser(): void
    {
        $compensation = $this->createCompensation('BA Single Forbidden Comp');
        $owner = $this->createUser('ba_owner_forbidden');
        $otherUser = $this->createUser('ba_other_forbidden');
        $assignment = $this->createAssignment($owner, $compensation);
        $token = $this->getUserTokenFromUser($otherUser);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/benefit_assignments/'.$assignment->getId()
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetSingleUnauthenticatedReturns401(): void
    {
        $compensation = $this->createCompensation('BA Single Unauth Comp');
        $owner = $this->createUser('ba_owner_unauth');
        $assignment = $this->createAssignment($owner, $compensation);

        static::createClient()->request('GET', '/api/benefit_assignments/'.$assignment->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetMeBenefitAssignmentsAsOwnerReturnsOwnAssignments(): void
    {
        $compensation = $this->createCompensation('BA Me Owner Comp');
        $employee = $this->createUser('ba_me_employee');
        $this->createAssignment($employee, $compensation);
        $token = $this->getUserTokenFromUser($employee);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/me/benefit_assignments.jsonld'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/BenefitAssignment',
            '@type' => 'hydra:Collection',
        ]);

        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, $data['hydra:totalItems']);
    }

    public function testGetMeBenefitAssignmentsUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/me/benefit_assignments.jsonld');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostAsAdminCreatesAssignment(): void
    {
        $compensation = $this->createCompensation('BA Post Admin Comp');
        $employee = $this->createUser('ba_employee_post_admin');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/benefit_assignments',
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-06-01T09:00:00+00:00',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/BenefitAssignment',
            '@type' => 'BenefitAssignment',
        ]);
    }

    public function testPostAsHrUserCreatesAssignment(): void
    {
        $compensation = $this->createCompensation('BA Post HR Comp');
        $employee = $this->createUser('ba_employee_post_hr');
        $hr = $this->createUser('ba_hr_post', 'ba_hr_post', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/benefit_assignments',
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-06-15T10:00:00+00:00',
                        'comment' => 'Annual benefit',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['comment' => 'Annual benefit']);
    }

    public function testPostWithAllOptionalFields(): void
    {
        $compensation = $this->createCompensation('BA Post Full Comp');
        $employee = $this->createUser('ba_employee_post_full');
        $token = $this->getUserToken();

        $response = $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/benefit_assignments',
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-01-01T00:00:00+00:00',
                        'assignmentEndDatetime' => '2025-12-31T23:59:59+00:00',
                        'economicalEquivalent' => 1500.50,
                        'comment' => 'Full fields assignment',
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(1500.5, $data['economicalEquivalent']);
        $this->assertNotNull($data['assignmentEndDatetime']);
    }

    public function testPostUnauthenticatedReturns401(): void
    {
        static::createClient()
            ->request(
                'POST',
                '/api/benefit_assignments',
                [
                    'json' => [
                        'user' => '/api/users/1',
                        'compensation' => '/api/compensations/1',
                        'assignmentDatetime' => '2025-01-01T00:00:00+00:00',
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostAsStudentReturns403(): void
    {
        $student = $this->createUser('ba_student_post');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/benefit_assignments',
                [
                    'json' => [
                        'user' => '/api/users/'.$student->getId(),
                        'compensation' => '/api/compensations/1',
                        'assignmentDatetime' => '2025-01-01T00:00:00+00:00',
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostMissingRequiredFieldsReturns422(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/benefit_assignments',
                ['json' => ['comment' => 'Missing required fields']]
            )
        ;

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutAsAdminUpdatesAssignment(): void
    {
        $compensation = $this->createCompensation('BA Put Admin Comp');
        $employee = $this->createUser('ba_employee_put_admin');
        $assignment = $this->createAssignment($employee, $compensation, 'Before update');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/benefit_assignments/'.$assignment->getId(),
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-03-01T08:00:00+00:00',
                        'comment' => 'After update',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['comment' => 'After update']);
    }

    public function testPutAsHrUserUpdatesAssignment(): void
    {
        $compensation = $this->createCompensation('BA Put HR Comp');
        $employee = $this->createUser('ba_employee_put_hr');
        $assignment = $this->createAssignment($employee, $compensation);
        $hr = $this->createUser('ba_hr_put', 'ba_hr_put', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/benefit_assignments/'.$assignment->getId(),
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-04-01T08:00:00+00:00',
                        'economicalEquivalent' => 2000.0,
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPutAsStudentReturns403(): void
    {
        $compensation = $this->createCompensation('BA Put Student Comp');
        $employee = $this->createUser('ba_employee_put_student');
        $assignment = $this->createAssignment($employee, $compensation);
        $student = $this->createUser('ba_student_put');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/benefit_assignments/'.$assignment->getId(),
                [
                    'json' => [
                        'user' => '/api/users/'.$employee->getId(),
                        'compensation' => '/api/compensations/'.$compensation->getId(),
                        'assignmentDatetime' => '2025-01-01T00:00:00+00:00',
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAsAdminReturns204(): void
    {
        $compensation = $this->createCompensation('BA Delete Admin Comp');
        $employee = $this->createUser('ba_employee_del_admin');
        $assignment = $this->createAssignment($employee, $compensation);
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/benefit_assignments/'.$assignment->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsHrUserReturns204(): void
    {
        $compensation = $this->createCompensation('BA Delete HR Comp');
        $employee = $this->createUser('ba_employee_del_hr');
        $assignment = $this->createAssignment($employee, $compensation);
        $hr = $this->createUser('ba_hr_del', 'ba_hr_del', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/benefit_assignments/'.$assignment->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsStudentReturns403(): void
    {
        $compensation = $this->createCompensation('BA Delete Student Comp');
        $employee = $this->createUser('ba_employee_del_student');
        $assignment = $this->createAssignment($employee, $compensation);
        $student = $this->createUser('ba_student_del');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/benefit_assignments/'.$assignment->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteNonExistentReturns404(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('DELETE', '/api/benefit_assignments/999999');

        $this->assertResponseStatusCodeSame(404);
    }
}
