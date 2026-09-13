<?php

namespace Tests\Unit;

use App\Exceptions\ReniecException;
use App\Services\ReniecService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReniecServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reniec.dni_usuario' => '87654321',
            'services.reniec.ruc_usuario' => '20123456789',
            'services.reniec.password' => 'credencial-segura',
            'services.reniec.consultar_url' => 'https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?out=json',
            'services.reniec.actualizar_url' => 'https://ws2.pide.gob.pe/Rest/RENIEC/Actualizar?out=json',
            'services.reniec.connect_timeout' => 5,
            'services.reniec.timeout' => 15,
        ]);
    }

    public function test_consultar_maps_person_and_sends_required_pide_payload(): void
    {
        $photoBase64 = base64_encode("\xFF\xD8\xFF\xD9");

        Http::fake([
            '*' => Http::response([
                'consultarResponse' => [
                    'return' => [
                        'coResultado' => '0000',
                        'deResultado' => 'Consulta realizada correctamente',
                        'datosPersona' => [
                            'apPrimer' => 'QUISPE',
                            'apSegundo' => 'MAMANI',
                            'prenombres' => 'MARIA ELENA',
                            'estadoCivil' => 'SOLTERO',
                            'foto' => $photoBase64,
                            'ubigeo' => 'PUNO/PUNO/PUNO',
                            'direccion' => 'JR. LIMA 123',
                            'restriccion' => 'NINGUNA',
                        ],
                    ],
                ],
            ]),
        ]);

        $person = app(ReniecService::class)->consultar('12345678');

        $this->assertSame('MARIA ELENA', $person['names']);
        $this->assertSame('QUISPE', $person['father_lastname']);
        $this->assertSame('MAMANI', $person['mother_lastname']);
        $this->assertSame('JR. LIMA 123', $person['address']);
        $this->assertSame('data:image/jpeg;base64,'.$photoBase64, $person['photo']);

        Http::assertSent(function (Request $request) {
            return $request->url() === config('services.reniec.consultar_url')
                && $request->header('Content-Type')[0] === 'application/json; charset=UTF-8'
                && $request['PIDE'] === [
                    'nuDniConsulta' => '12345678',
                    'nuDniUsuario' => '87654321',
                    'nuRucUsuario' => '20123456789',
                    'password' => 'credencial-segura',
                ];
        });
    }

    public function test_consultar_maps_reniec_error_code(): void
    {
        Http::fake(['*' => Http::response([
            'coResultado' => '1002',
            'deResultado' => 'Credencial caducada',
        ])]);

        try {
            app(ReniecService::class)->consultar('12345678');
            $this->fail('Se esperaba una excepción RENIEC.');
        } catch (ReniecException $exception) {
            $this->assertSame(503, $exception->httpStatus());
            $this->assertSame('1002', $exception->resultCode());
            $this->assertSame('La contraseña RENIEC está caducada.', $exception->getMessage());
        }
    }

    public function test_actualizar_credencial_sends_required_pide_payload(): void
    {
        Http::fake(['*' => Http::response([
            'coResultado' => '0000',
            'deResultado' => 'Credencial actualizada',
        ])]);

        $result = app(ReniecService::class)->actualizarCredencial('anterior', 'nueva');

        $this->assertSame('0000', $result['coResultado']);
        Http::assertSent(function (Request $request) {
            return $request->url() === config('services.reniec.actualizar_url')
                && $request['PIDE'] === [
                    'credencialAnterior' => 'anterior',
                    'credencialNueva' => 'nueva',
                    'nuDni' => '87654321',
                    'nuRuc' => '20123456789',
                ];
        });
    }
}
