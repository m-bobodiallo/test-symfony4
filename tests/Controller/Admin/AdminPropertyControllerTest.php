<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use App\Tests\FixturesTrait;
use App\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminPropertyControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @var PropertyRepository
     */
    protected PropertyRepository $propertyRepository;

    public function testIndexAdminProperties(): void
    {
        $this->loadData();
        $this->logAdmin();
        $this->client->request('GET', '/admin/properties');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->expectTitle('Gérer des biens');
    }

    /**
     * @return object[]
     */
    private function loadData(): array
    {
        return $this->loadFixtures(['properties']);
    }

    public function testShowFormEditProperty(): void
    {
        /** @var Property $property **/
        ['property1' => $property] = $this->loadData();

        $this->logAdmin();
        $this->client->request('GET', '/admin/properties/' . $property->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->expectH1('Editer le bien');
    }

    public function testShowFormCreateProperty(): void
    {
        $this->logAdmin();
        $this->client->request('GET', '/admin/properties/create');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->expectH1('Ajouter un bien');
    }

    public function testCreateNewProperty(): void
    {
        $this->logAdmin();
        $count = $this->propertyRepository->count([]);

        $crawler = $this->client->request('GET', '/admin/properties/create');
        $form = $crawler->selectButton('Ajouter')->form([
            'property[title]' => 'Un nouveau bien',
            'property[description]' => 'Description du nuveau bien',
            'property[surface]' => 50,
            'property[rooms]' => '5',
            'property[bedrooms]' => '20',
            'property[floor]' => '1',
            'property[price]' => '5000',
            'property[heat]' => '0',
            'property[city]' => 'Dakar City',
            'property[address]' => 'Scat Urbam',
            'property[postal_code]' => '16000',
            'property[sold]' => '1',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/properties');
        $this->assertEquals($count + 1, $this->propertyRepository->count([]));
        $this->client->followRedirect();
        $this->expectSuccessAlert();
    }

    public function stestEditProperty(): void
    {
        $this->logAdmin();

        /** @var Property $property */
        ['property1' => $property] = $this->loadData();

        $crawler = $this->client->request('GET', '/admin/properties/' . $property->getId());
        $form = $crawler->selectButton('Editer')->form([
            'property[title]' => 'Un bien modifié',
            'property[description]' => 'Description d\'un bien modifiéss',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/admin/properties/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-success', 'Bien modifié avec success');
    }

    public function stestDeleteProperty(): void
    {
        $this->logAdmin();
        ['count' => $count] = $this->loadDataAndCountRows();

        $crawler = $this->client->request('GET', '/admin/properties');
        $form = $crawler
            ->selectButton('Supprimer')
            ->eq(0)
            ->form();

        $this->client->submit($form);
        $rows = $this->propertyRepository->count([]);
        $this->assertEquals($count - 1, $rows);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-success', 'Bien supprimé avec success');
    }

    /**
     * @return array<mixed>
     */
    public function loadDataAndCountRows(): array
    {
        return [
            'property' => $this->loadData()['property1'],
            'count' => $this->propertyRepository->count([])
        ];
    }

    public function stestNotDeletePropertyWithInvalidToken(): void
    {
        $this->logAdmin();
        ['count' => $count] = $this->loadDataAndCountRows();

        $crawler = $this->client->request('GET', '/admin/properties');
        $form = $crawler
            ->selectButton('Supprimer')
            ->eq(0)
            ->form([
                '_method' => 'DELETE',
                '_token' => 'invalidToken',
            ]);
        $this->client->submit($form);

        $this->assertEquals($count, $this->propertyRepository->count([]));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Gérer les biens');
    }

    public function stestDeletePropertyWithValidToken(): void
    {
        $this->logAdmin();
        ['property' => $property, 'count' => $count] = $this->loadDataAndCountRows();

        $crawler = $this->client->request('GET', '/admin/properties');
        $csrfToken = $this->client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('delete' . $property->getId());

        $form = $crawler
            ->selectButton('Supprimer')
            ->eq(0)
            ->form([
                '_method' => 'DELETE',
                '_token' => $csrfToken,
            ]);
        $this->client->submit($form);
        $this->assertEquals($count - 1, $this->propertyRepository->count([]));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyRepository = self::$container->get(PropertyRepository::class);
    }

    public function logAdmin()
    {
        $users = $this->loadFixtures(['users']);
        $this->login($users['user-user']);
    }
}
