<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Compensation;
use Chamilo\CoreBundle\Entity\CompensationTag;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CompensationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function createTag(string $title, string $color = '#3B82F6'): CompensationTag
    {
        $em = $this->getEntityManager();
        $tag = (new CompensationTag())
            ->setTitle($title)
            ->setColor($color)
        ;
        $em->persist($tag);
        $em->flush();

        return $tag;
    }

    private function createCompensation(string $title, float $score = 5.0): Compensation
    {
        $em = $this->getEntityManager();
        $admin = $this->getAdmin();

        $compensation = (new Compensation())
            ->setTitle($title)
            ->setScore($score)
            ->setAuthor($admin)
        ;
        $em->persist($compensation);
        $em->flush();

        return $compensation;
    }

    public function testGetCollectionAsAdminReturns200(): void
    {
        $this->createCompensation('Comp Collection Admin');

        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations')
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Compensation',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetCollectionAsHrUserReturns200(): void
    {
        $hr = $this->createUser('comp_hr_coll', 'comp_hr_coll', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations')
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCollectionUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/compensations');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCollectionAsStudentReturns403(): void
    {
        $student = $this->createUser('comp_student_coll');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations')
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetSingleAsAdminReturns200(): void
    {
        $compensation = $this->createCompensation('Comp Single Admin', 7.5);
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations/'.$compensation->getId())
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Compensation',
            '@type' => 'Compensation',
            'title' => 'Comp Single Admin',
            'score' => 7.5,
        ]);
    }

    public function testGetSingleAsHrUserReturns200(): void
    {
        $compensation = $this->createCompensation('Comp Single HR');
        $hr = $this->createUser('comp_hr_single', 'comp_hr_single', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations/'.$compensation->getId())
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetSingleUnauthenticatedReturns401(): void
    {
        $compensation = $this->createCompensation('Comp Single Unauth');

        static::createClient()
            ->request('GET', '/api/compensations/'.$compensation->getId())
        ;

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetSingleAsStudentReturns403(): void
    {
        $compensation = $this->createCompensation('Comp Single Student');
        $student = $this->createUser('comp_student_single');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensations/'.$compensation->getId())
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostAsAdminCreatesCompensation(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensations',
                [
                    'json' => [
                        'title' => 'New Compensation Admin',
                        'score' => 10.0,
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Compensation',
            '@type' => 'Compensation',
            'title' => 'New Compensation Admin',
            'score' => 10,
        ]);
    }

    public function testPostAsHrUserCreatesCompensation(): void
    {
        $hr = $this->createUser('comp_hr_post', 'comp_hr_post', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensations',
                [
                    'json' => [
                        'title' => 'New Compensation HR',
                        'score' => 8.0,
                        'description' => 'HR compensation',
                        'duration' => '12 months',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'title' => 'New Compensation HR',
            'description' => 'HR compensation',
            'duration' => '12 months',
        ]);
    }

    public function testPostWithTagsCreatesCompensationWithTags(): void
    {
        $tag = $this->createTag('Comp Post Tag');
        $token = $this->getUserToken();

        $response = $this->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensations',
                [
                    'json' => [
                        'title' => 'Compensation With Tags',
                        'score' => 5.0,
                        'tags' => ['/api/compensation_tags/'.$tag->getId()],
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertNotEmpty($data['tags']);
    }

    public function testPostUnauthenticatedReturns401(): void
    {
        static::createClient()
            ->request(
                'POST',
                '/api/compensations',
                ['json' => ['title' => 'Unauth Comp', 'score' => 1.0]]
            )
        ;

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostAsStudentReturns403(): void
    {
        $student = $this->createUser('comp_student_post');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensations',
                ['json' => ['title' => 'Student Comp', 'score' => 1.0]]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostMissingTitleReturns422(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensations',
                ['json' => ['score' => 5.0]]
            )
        ;

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutAsAdminUpdatesCompensation(): void
    {
        $compensation = $this->createCompensation('Comp Before Put', 3.0);
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensations/'.$compensation->getId(),
                [
                    'json' => [
                        'title' => 'Comp After Put',
                        'score' => 9.0,
                        'description' => 'Updated description',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'title' => 'Comp After Put',
            'score' => 9,
            'description' => 'Updated description',
        ]);
    }

    public function testPutAsHrUserUpdatesCompensation(): void
    {
        $compensation = $this->createCompensation('Comp HR Before Put', 4.0);
        $hr = $this->createUser('comp_hr_put', 'comp_hr_put', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensations/'.$compensation->getId(),
                ['json' => ['title' => 'Comp HR After Put', 'score' => 6.0]]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPutAsStudentReturns403(): void
    {
        $compensation = $this->createCompensation('Comp Student Put', 2.0);
        $student = $this->createUser('comp_student_put');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensations/'.$compensation->getId(),
                ['json' => ['title' => 'Overwritten', 'score' => 0.0]]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPutCanUpdateTagsRelation(): void
    {
        $compensation = $this->createCompensation('Comp Tags Update', 5.0);
        $tag = $this->createTag('Tag For Put');
        $token = $this->getUserToken();

        $response = $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensations/'.$compensation->getId(),
                [
                    'json' => [
                        'title' => 'Comp Tags Update',
                        'score' => 5.0,
                        'tags' => ['/api/compensation_tags/'.$tag->getId()],
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertNotEmpty($data['tags']);
    }

    public function testDeleteAsAdminReturns204(): void
    {
        $compensation = $this->createCompensation('Comp To Delete Admin');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensations/'.$compensation->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsHrUserReturns204(): void
    {
        $compensation = $this->createCompensation('Comp To Delete HR');
        $hr = $this->createUser('comp_hr_del', 'comp_hr_del', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensations/'.$compensation->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsStudentReturns403(): void
    {
        $compensation = $this->createCompensation('Comp Student Delete');
        $student = $this->createUser('comp_student_del');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensations/'.$compensation->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteNonExistentReturns404(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('DELETE', '/api/compensations/999999')
        ;

        $this->assertResponseStatusCodeSame(404);
    }
}
