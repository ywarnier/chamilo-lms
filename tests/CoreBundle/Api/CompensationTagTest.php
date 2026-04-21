<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\CompensationTag;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CompensationTagTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function createTag(string $title, string $color = '#FF0000'): CompensationTag
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

    public function testGetCollectionAsAdminReturns200(): void
    {
        $this->createTag('Tag Collection Admin');

        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags')
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/CompensationTag',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetCollectionAsHrUserReturns200(): void
    {
        $hr = $this->createUser('ct_hr_coll', 'ct_hr_coll', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags')
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCollectionUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/compensation_tags');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCollectionAsStudentReturns403(): void
    {
        $student = $this->createUser('ct_student_coll');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags')
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetSingleAsAdminReturns200(): void
    {
        $tag = $this->createTag('Tag Single Admin', '#AABBCC');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags/'.$tag->getId())
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CompensationTag',
            '@type' => 'CompensationTag',
            'title' => 'Tag Single Admin',
            'color' => '#AABBCC',
        ]);
    }

    public function testGetSingleAsHrUserReturns200(): void
    {
        $tag = $this->createTag('Tag Single HR');
        $hr = $this->createUser('ct_hr_single', 'ct_hr_single', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags/'.$tag->getId())
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetSingleUnauthenticatedReturns401(): void
    {
        $tag = $this->createTag('Tag Single Unauth');

        static::createClient()
            ->request('GET', '/api/compensation_tags/'.$tag->getId())
        ;

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetSingleAsStudentReturns403(): void
    {
        $tag = $this->createTag('Tag Single Student');
        $student = $this->createUser('ct_student_single');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/compensation_tags/'.$tag->getId())
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostAsAdminCreatesTag(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensation_tags',
                [
                    'json' => [
                        'title' => 'New Tag Admin',
                        'color' => '#123456',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CompensationTag',
            '@type' => 'CompensationTag',
            'title' => 'New Tag Admin',
            'color' => '#123456',
        ]);
    }

    public function testPostAsHrUserCreatesTag(): void
    {
        $hr = $this->createUser('ct_hr_post', 'ct_hr_post', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensation_tags',
                [
                    'json' => [
                        'title' => 'New Tag HR',
                        'color' => '#654321',
                        'description' => 'HR created tag',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'title' => 'New Tag HR',
            'description' => 'HR created tag',
        ]);
    }

    public function testPostWithDefaultColorReturnsBlue(): void
    {
        $token = $this->getUserToken();

        $response = $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensation_tags',
                ['json' => ['title' => 'Tag Default Color', 'color' => '#3B82F6']]
            )
        ;

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('#3B82F6', $data['color']);
    }

    public function testPostUnauthenticatedReturns401(): void
    {
        static::createClient()->request(
            'POST',
            '/api/compensation_tags',
            ['json' => ['title' => 'Unauth Tag', 'color' => '#000000']]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostAsStudentReturns403(): void
    {
        $student = $this->createUser('ct_student_post');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/compensation_tags',
                ['json' => ['title' => 'Student Tag', 'color' => '#000000']]
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
                '/api/compensation_tags',
                ['json' => ['color' => '#000000']]
            )
        ;

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutAsAdminUpdatesTag(): void
    {
        $tag = $this->createTag('Tag Before Put', '#111111');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensation_tags/'.$tag->getId(),
                [
                    'json' => [
                        'title' => 'Tag After Put',
                        'color' => '#222222',
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'title' => 'Tag After Put',
            'color' => '#222222',
        ]);
    }

    public function testPutAsHrUserUpdatesTag(): void
    {
        $tag = $this->createTag('Tag HR Before Put', '#333333');
        $hr = $this->createUser('ct_hr_put', 'ct_hr_put', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensation_tags/'.$tag->getId(),
                ['json' => ['title' => 'Tag HR After Put', 'color' => '#444444']]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPutAsStudentReturns403(): void
    {
        $tag = $this->createTag('Tag Student Put', '#555555');
        $student = $this->createUser('ct_student_put');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'PUT',
                '/api/compensation_tags/'.$tag->getId(),
                ['json' => ['title' => 'Overwritten', 'color' => '#000000']]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAsAdminReturns204(): void
    {
        $tag = $this->createTag('Tag To Delete Admin', '#666666');
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensation_tags/'.$tag->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsHrUserReturns204(): void
    {
        $tag = $this->createTag('Tag To Delete HR', '#777777');
        $hr = $this->createUser('ct_hr_del', 'ct_hr_del', '', 'ROLE_HR');
        $token = $this->getUserTokenFromUser($hr);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensation_tags/'.$tag->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsStudentReturns403(): void
    {
        $tag = $this->createTag('Tag Student Delete', '#888888');
        $student = $this->createUser('ct_student_del');
        $token = $this->getUserTokenFromUser($student);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'DELETE',
                '/api/compensation_tags/'.$tag->getId()
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteNonExistentReturns404(): void
    {
        $token = $this->getUserToken();

        $this
            ->createClientWithCredentials($token)
            ->request('DELETE', '/api/compensation_tags/999999');

        $this->assertResponseStatusCodeSame(404);
    }
}
