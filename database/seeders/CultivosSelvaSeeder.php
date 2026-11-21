<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CultivosSelvaSeeder extends Seeder
{
    public function run(): void
    {
        $cultivos = [
            // AMAZONAS/SAN MARTÍN/LORETO - CACAO
            [
                'nombreCultivo' => 'Cacao',
                'nombreCientifico' => 'Theobroma cacao',
                'tipo' => 'Otros',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 5,
                'frecuenciaRiegoMax' => 7,
                'litrosPorM2Dia' => 4.00,
                'humedadOptimaMin' => 75.00,
                'humedadOptimaMax' => 85.00,
                'sensibilidadSequia' => 'Alta',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Desarrollo de mazorcas',
                'diasEtapaConsumoMin' => null,
                'diasEtapaConsumoMax' => null,
                'reduccionConHidrogel' => 22.50,
                'tempCriticaMinima' => 10.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Daño al follaje',
                'efectoTempMaxima' => 'Reduce floración',
                'cicloProductivo' => 365,
                'descripcion' => 'Cultivo tropical de exportación',
                'observaciones' => 'Época seca: 5-7 días. Natural con lluvias. Producción todo el año'
            ],

            // SELVA - FRIJOL GRANO SECO
            [
                'nombreCultivo' => 'Frijol Grano Seco',
                'nombreCientifico' => 'Phaseolus vulgaris',
                'tipo' => 'Legumbres',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 6,
                'frecuenciaRiegoMax' => 8,
                'litrosPorM2Dia' => 2.50,
                'humedadOptimaMin' => 60.00,
                'humedadOptimaMax' => 70.00,
                'sensibilidadSequia' => 'Media',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Floración y llenado',
                'diasEtapaConsumoMin' => 40,
                'diasEtapaConsumoMax' => 80,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => 8.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Alta',
                'efectoTempMinima' => 'Detiene crecimiento',
                'efectoTempMaxima' => 'Aborto floral',
                'cicloProductivo' => 90,
                'descripcion' => 'Leguminosa de consumo nacional',
                'observaciones' => 'Crítico en floración'
            ],

            // SELVA - YUCA
            [
                'nombreCultivo' => 'Yuca',
                'nombreCientifico' => 'Manihot esculenta',
                'tipo' => 'Tubérculos',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 10,
                'frecuenciaRiegoMax' => 15,
                'litrosPorM2Dia' => 3.00,
                'humedadOptimaMin' => 60.00,
                'humedadOptimaMax' => 70.00,
                'sensibilidadSequia' => 'Baja',
                'diasCriticosSinAgua' => 20,
                'etapaMayorConsumo' => 'Engrosamiento de raíces',
                'diasEtapaConsumoMin' => 120,
                'diasEtapaConsumoMax' => 300,
                'reduccionConHidrogel' => 22.50,
                'tempCriticaMinima' => 15.00,
                'tempCriticaMaxima' => 38.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce crecimiento',
                'efectoTempMaxima' => 'Estrés hídrico',
                'cicloProductivo' => 300,
                'descripcion' => 'Tubérculo tropical muy resistente',
                'observaciones' => 'Muy resistente a sequía'
            ],

            // SELVA - PALMA ACEITERA
            [
                'nombreCultivo' => 'Palma Aceitera',
                'nombreCientifico' => 'Elaeis guineensis',
                'tipo' => 'Oleaginosas',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 7,
                'frecuenciaRiegoMax' => 10,
                'litrosPorM2Dia' => 5.00,
                'humedadOptimaMin' => 75.00,
                'humedadOptimaMax' => 85.00,
                'sensibilidadSequia' => 'Media-Alta',
                'diasCriticosSinAgua' => 15,
                'etapaMayorConsumo' => 'Producción de racimos',
                'diasEtapaConsumoMin' => null,
                'diasEtapaConsumoMax' => null,
                'reduccionConHidrogel' => 17.50,
                'tempCriticaMinima' => 18.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce producción',
                'efectoTempMaxima' => 'Estrés',
                'cicloProductivo' => 365,
                'descripcion' => 'Oleaginosa tropical de alto rendimiento',
                'observaciones' => 'Época seca: 7-10 días. Natural con lluvias. Producción todo el año'
            ],

            // SELVA - SACHA INCHI
            [
                'nombreCultivo' => 'Sacha Inchi',
                'nombreCientifico' => 'Plukenetia volubilis',
                'tipo' => 'Oleaginosas',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 5,
                'frecuenciaRiegoMax' => 7,
                'litrosPorM2Dia' => 3.50,
                'humedadOptimaMin' => 70.00,
                'humedadOptimaMax' => 80.00,
                'sensibilidadSequia' => 'Media-Alta',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Floración y fructificación',
                'diasEtapaConsumoMin' => null,
                'diasEtapaConsumoMax' => null,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => 15.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce floración',
                'efectoTempMaxima' => 'Caída de flores',
                'cicloProductivo' => 365,
                'descripcion' => 'Oleaginosa amazónica de exportación',
                'observaciones' => 'Floración y fructificación continua'
            ],

            // SELVA - CAMU CAMU
            [
                'nombreCultivo' => 'Camu Camu',
                'nombreCientifico' => 'Myrciaria dubia',
                'tipo' => 'Frutales',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 1,
                'frecuenciaRiegoMax' => 1,
                'litrosPorM2Dia' => 0.00,
                'humedadOptimaMin' => 80.00,
                'humedadOptimaMax' => 100.00,
                'sensibilidadSequia' => 'Alta',
                'diasCriticosSinAgua' => 7,
                'etapaMayorConsumo' => 'Fructificación',
                'diasEtapaConsumoMin' => 274,
                'diasEtapaConsumoMax' => 365,
                'reduccionConHidrogel' => null,
                'tempCriticaMinima' => 18.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce crecimiento',
                'efectoTempMaxima' => 'Estrés',
                'cicloProductivo' => 365,
                'descripcion' => 'Frutal ribereño de alto contenido vitamina C',
                'observaciones' => 'Natural. Crece en zonas inundables. Tolera inundación. No aplica hidrogel. Oct-marzo fructificación'
            ],

            // SELVA - PALMITO
            [
                'nombreCultivo' => 'Palmito',
                'nombreCientifico' => 'Bactris gasipaes',
                'tipo' => 'Hortalizas',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 5,
                'frecuenciaRiegoMax' => 7,
                'litrosPorM2Dia' => 4.50,
                'humedadOptimaMin' => 75.00,
                'humedadOptimaMax' => 85.00,
                'sensibilidadSequia' => 'Alta',
                'diasCriticosSinAgua' => 10,
                'etapaMayorConsumo' => 'Crecimiento del tallo',
                'diasEtapaConsumoMin' => null,
                'diasEtapaConsumoMax' => null,
                'reduccionConHidrogel' => 22.50,
                'tempCriticaMinima' => 15.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce crecimiento',
                'efectoTempMaxima' => 'Estrés',
                'cicloProductivo' => 540,
                'descripcion' => 'Palma para palmito de exportación',
                'observaciones' => 'Crecimiento continuo del tallo'
            ],

            // SELVA - PAPAYA
            [
                'nombreCultivo' => 'Papaya',
                'nombreCientifico' => 'Carica papaya',
                'tipo' => 'Frutales',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 3,
                'frecuenciaRiegoMax' => 5,
                'litrosPorM2Dia' => 4.00,
                'humedadOptimaMin' => 70.00,
                'humedadOptimaMax' => 80.00,
                'sensibilidadSequia' => 'Alta',
                'diasCriticosSinAgua' => 7,
                'etapaMayorConsumo' => 'Fructificación',
                'diasEtapaConsumoMin' => 210,
                'diasEtapaConsumoMax' => 1095,
                'reduccionConHidrogel' => 27.50,
                'tempCriticaMinima' => 15.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Detiene crecimiento',
                'efectoTempMaxima' => 'Quemadura de frutos',
                'cicloProductivo' => 365,
                'descripcion' => 'Frutal tropical de rápido crecimiento',
                'observaciones' => 'Fructificación 7-36 meses'
            ],

            // SELVA - CASTAÑA
            [
                'nombreCultivo' => 'Castaña',
                'nombreCientifico' => 'Bertholletia excelsa',
                'tipo' => 'Otros',
                'region' => 'Selva',
                'frecuenciaRiegoMin' => 1,
                'frecuenciaRiegoMax' => 1,
                'litrosPorM2Dia' => 0.00,
                'humedadOptimaMin' => 70.00,
                'humedadOptimaMax' => 80.00,
                'sensibilidadSequia' => 'Baja',
                'diasCriticosSinAgua' => 30,
                'etapaMayorConsumo' => 'Fructificación',
                'diasEtapaConsumoMin' => 244,
                'diasEtapaConsumoMax' => 365,
                'reduccionConHidrogel' => null,
                'tempCriticaMinima' => 18.00,
                'tempCriticaMaxima' => 35.00,
                'sensibilidadHeladas' => 'Muy Alta',
                'efectoTempMinima' => 'Reduce producción',
                'efectoTempMaxima' => 'Normal en Amazonía',
                'cicloProductivo' => 365,
                'descripcion' => 'Árbol silvestre amazónico de recolección',
                'observaciones' => 'Natural. Árbol silvestre. Raíces profundas. No aplica hidrogel (extractivo). Sep-marzo fructificación'
            ],
        ];

        DB::table('cultivos')->insert($cultivos);
    }
}