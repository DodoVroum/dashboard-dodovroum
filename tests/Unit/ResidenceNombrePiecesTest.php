<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\StoreResidenceRequest;
use App\Http\Requests\Admin\UpdateResidenceRequest;
use App\Services\DodoVroumApi\Mappers\ResidenceMapper;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * « Nombre de pièces » : obligatoire dans le dashboard, distinct du nombre de
 * chambres, et absent (null) sur les résidences créées avant l'ajout du champ.
 */
class ResidenceNombrePiecesTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Villa test',
            'typeResidence' => 'villa',
            'address' => '1 rue test',
            'city' => 'Abidjan',
            'pricePerNight' => 100,
            'nombrePieces' => 3,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'capacity' => 4,
            'proprietaireId' => 'owner-1',
        ], $overrides);
    }

    public static function requestProvider(): array
    {
        return [
            'création' => [StoreResidenceRequest::class],
            'modification' => [UpdateResidenceRequest::class],
        ];
    }

    /** @dataProvider requestProvider */
    public function test_accepts_positive_integer(string $requestClass): void
    {
        foreach ([1, 2, 3] as $n) {
            $validator = Validator::make($this->payload(['nombrePieces' => $n]), (new $requestClass)->rules());
            $this->assertFalse($validator->fails(), "nombrePieces={$n} devrait être accepté");
        }
    }

    /** @dataProvider requestProvider */
    public function test_rejects_missing_or_invalid_values(string $requestClass): void
    {
        $payloadWithout = $this->payload();
        unset($payloadWithout['nombrePieces']);

        foreach ([$payloadWithout, $this->payload(['nombrePieces' => 0]), $this->payload(['nombrePieces' => -1]),
            $this->payload(['nombrePieces' => 2.5]), $this->payload(['nombrePieces' => 'abc'])] as $data) {
            $validator = Validator::make($data, (new $requestClass)->rules());
            $this->assertTrue($validator->errors()->has('nombrePieces'), json_encode($data['nombrePieces'] ?? null));
        }
    }

    public function test_mapper_keeps_nombre_pieces_distinct_from_bedrooms(): void
    {
        $mapped = ResidenceMapper::fromApi(['id' => 'r1', 'nombrePieces' => 4, 'bedrooms' => 2]);
        $this->assertSame(4, $mapped['nombrePieces']);
        $this->assertSame(2, $mapped['bedrooms']);

        $toApi = ResidenceMapper::toApi(['nombrePieces' => 4, 'bedrooms' => 2]);
        $this->assertSame(4, $toApi['nombrePieces']);
        $this->assertSame(2, $toApi['bedrooms']);
    }

    public function test_mapper_returns_null_for_legacy_residence(): void
    {
        $mapped = ResidenceMapper::fromApi(['id' => 'legacy', 'bedrooms' => 2]);
        $this->assertArrayHasKey('nombrePieces', $mapped);
        $this->assertNull($mapped['nombrePieces']);
    }
}
