<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CultivosSierraSeeder extends Seeder
{
    public function run(): void
    {
        $cultivos = [
            // CAJAMARCA - CAFÉ
            [
                'nombreCultivo' => 'Café',
                'nombreCientifico' => 'Coffea arabica',
                'tipo' => 'Otros',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 7,
                'frecuenciaRiegoMax' => 10,
                'litrosPorM2Dia' => 3.00,
                'humedadOptimaMin' => 70.00,
                'humedadOptimaMax' => 80.00,
                'sensibilidadSequia' => 'Media',
                'diasCriticosSinAgua' => 15,
                'etapaMayorConsumo' => 'Desarrollo del fruto',
                'diasEtapaConsumoMin' => 350,
                'diasEtapaConsumoMax' => 365,
                'reduccionConHidrogel' => 22.50,
                'tempCriticaMinima' => 5.00,
                'tempCriticaMaxima' => 32.00,
                'sensibilidadHeladas' => 'Alta',
                'efectoTempMinima' => 'Daño al follaje',
                'efectoTempMaxima' => 'Reduce calidad',
                'cicloProductivo' => 365,
                'descripcion' => 'Cultivo de exportación de Cajamarca',
                'observaciones' => 'Época seca: 7-10 días. Natural con lluvias. Crítico en floración. Dic-abril mayor consumo'
            ],

            // PUNO - PAPA SIERRA
            [
                'nombreCultivo' => 'Papa (Sierra)',
                'nombreCientifico' => 'Solanum tuberosum',
                'tipo' => 'Tubérculos',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 8,
                'frecuenciaRiegoMax' => 12,
                'litrosPorM2Dia' => 2.50,
                'humedadOptimaMin' => 60.00,
                'humedadOptimaMax' => 70.00,
                'sensibilidadSequia' => 'Alta',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Tuberización',
                'diasEtapaConsumoMin' => 70,
                'diasEtapaConsumoMax' => 110,
                'reduccionConHidrogel' => 32.50,
                'tempCriticaMinima' => -3.00,
                'tempCriticaMaxima' => 25.00,
                'sensibilidadHeladas' => 'Alta',
                'efectoTempMinima' => 'Helada mortal',
                'efectoTempMaxima' => 'Reduce tuberización',
                'cicloProductivo' => 150,
                'descripcion' => 'Tubérculo andino principal',
                'observaciones' => 'Complementario al secano. Muy útil hidrogel. Crítico en tuberización'
            ],

            // PUNO - QUINUA
            [
                'nombreCultivo' => 'Quinua',
                'nombreCientifico' => 'Chenopodium quinoa',
                'tipo' => 'Cereales',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 15,
                'frecuenciaRiegoMax' => 20,
                'litrosPorM2Dia' => 1.50,
                'humedadOptimaMin' => 55.00,
                'humedadOptimaMax' => 65.00,
                'sensibilidadSequia' => 'Baja',
                'diasCriticosSinAgua' => 25,
                'etapaMayorConsumo' => 'Floración y llenado de grano',
                'diasEtapaConsumoMin' => 90,
                'diasEtapaConsumoMax' => 150,
                'reduccionConHidrogel' => 32.50,
                'tempCriticaMinima' => -4.00,
                'tempCriticaMaxima' => 30.00,
                'sensibilidadHeladas' => 'Baja',
                'efectoTempMinima' => 'Tolera heladas',
                'efectoTempMaxima' => 'Reduce producción',
                'cicloProductivo' => 180,
                'descripcion' => 'Pseudocereal andino de exportación',
                'observaciones' => 'Muy resistente. Crítico en floración'
            ],

            // PUNO - CAÑIHUA
            [
                'nombreCultivo' => 'Cañihua',
                'nombreCientifico' => 'Chenopodium pallidicaule',
                'tipo' => 'Cereales',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 20,
                'frecuenciaRiegoMax' => 25,
                'litrosPorM2Dia' => 1.00,
                'humedadOptimaMin' => 50.00,
                'humedadOptimaMax' => 60.00,
                'sensibilidadSequia' => 'Muy Baja',
                'diasCriticosSinAgua' => 30,
                'etapaMayorConsumo' => 'Floración',
                'diasEtapaConsumoMin' => 70,
                'diasEtapaConsumoMax' => 90,
                'reduccionConHidrogel' => 32.50,
                'tempCriticaMinima' => -8.00,
                'tempCriticaMaxima' => 25.00,
                'sensibilidadHeladas' => 'Muy Baja',
                'efectoTempMinima' => 'Muy resistente',
                'efectoTempMaxima' => 'Reduce producción',
                'cicloProductivo' => 150,
                'descripcion' => 'Pseudocereal extremadamente resistente',
                'observaciones' => 'Extremadamente resistente a sequía y heladas'
            ],

            // PUNO/SIERRA - MAÍZ AMILÁCEO
            [
                'nombreCultivo' => 'Maíz Amiláceo',
                'nombreCientifico' => 'Zea mays',
                'tipo' => 'Cereales',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 10,
                'frecuenciaRiegoMax' => 15,
                'litrosPorM2Dia' => 2.50,
                'humedadOptimaMin' => 60.00,
                'humedadOptimaMax' => 70.00,
                'sensibilidadSequia' => 'Media',
                'diasCriticosSinAgua' => 12,
                'etapaMayorConsumo' => 'Floración y llenado',
                'diasEtapaConsumoMin' => 70,
                'diasEtapaConsumoMax' => 170,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => 0.00,
                'tempCriticaMaxima' => 32.00,
                'sensibilidadHeladas' => 'Alta',
                'efectoTempMinima' => 'Detiene crecimiento',
                'efectoTempMaxima' => 'Reduce llenado',
                'cicloProductivo' => 210,
                'descripcion' => 'Maíz harinoso andino',
                'observaciones' => 'Complementario al secano. Crítico en floración'
            ],

            // SIERRA - HABAS
            [
                'nombreCultivo' => 'Habas',
                'nombreCientifico' => 'Vicia faba',
                'tipo' => 'Legumbres',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 10,
                'frecuenciaRiegoMax' => 15,
                'litrosPorM2Dia' => 2.00,
                'humedadOptimaMin' => 60.00,
                'humedadOptimaMax' => 70.00,
                'sensibilidadSequia' => 'Media',
                'diasCriticosSinAgua' => 15,
                'etapaMayorConsumo' => 'Floración y llenado de vainas',
                'diasEtapaConsumoMin' => 60,
                'diasEtapaConsumoMax' => 140,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => -5.00,
                'tempCriticaMaxima' => 30.00,
                'sensibilidadHeladas' => 'Media',
                'efectoTempMinima' => 'Tolera heladas moderadas',
                'efectoTempMaxima' => 'Aborto floral',
                'cicloProductivo' => 180,
                'descripcion' => 'Leguminosa andina tradicional',
                'observaciones' => 'Complementario al secano. Crítico en floración'
            ],

            // SIERRA - ARVEJA
            [
                'nombreCultivo' => 'Arveja',
                'nombreCientifico' => 'Pisum sativum',
                'tipo' => 'Legumbres',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 8,
                'frecuenciaRiegoMax' => 12,
                'litrosPorM2Dia' => 2.50,
                'humedadOptimaMin' => 65.00,
                'humedadOptimaMax' => 75.00,
                'sensibilidadSequia' => 'Media',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Floración y llenado',
                'diasEtapaConsumoMin' => 45,
                'diasEtapaConsumoMax' => 90,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => -4.00,
                'tempCriticaMaxima' => 28.00,
                'sensibilidadHeladas' => 'Media',
                'efectoTempMinima' => 'Tolera heladas leves',
                'efectoTempMaxima' => 'Reduce cuajado',
                'cicloProductivo' => 120,
                'descripcion' => 'Leguminosa de consumo fresco',
                'observaciones' => 'Crítico en floración'
            ],

            // SIERRA - AVENA FORRAJERA
            [
                'nombreCultivo' => 'Avena Forrajera',
                'nombreCientifico' => 'Avena sativa',
                'tipo' => 'Cereales',
                'region' => 'Sierra',
                'frecuenciaRiegoMin' => 12,
                'frecuenciaRiegoMax' => 18,
                'litrosPorM2Dia' => 2.00,
                'humedadOptimaMin' => 55.00,
                'humedadOptimaMax' => 65.00,
                'sensibilidadSequia' => 'Baja',
                'diasCriticosSinAgua' => 20,
                'etapaMayorConsumo' => 'Macollamiento y encañado',
                'diasEtapaConsumoMin' => 30,
                'diasEtapaConsumoMax' => 70,
                'reduccionConHidrogel' => 22.50,
                'tempCriticaMinima' => -10.00,
                'tempCriticaMaxima' => 32.00,
                'sensibilidadHeladas' => 'Muy Baja',
                'efectoTempMinima' => 'Muy resistente',
                'efectoTempMaxima' => 'Reduce calidad',
                'cicloProductivo' => 120,
                'descripcion' => 'Forraje muy resistente al frío',
                'observaciones' => 'Muy resistente a heladas'
            ],
        ];

        DB::table('cultivos')->insert($cultivos);
    }
}