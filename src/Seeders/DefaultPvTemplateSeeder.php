<?php

namespace SalsabilEnnaiem\PvModule\Seeders;

use Illuminate\Database\Seeder;
use SalsabilEnnaiem\PvModule\Models\PvTemplate;

class DefaultPvTemplateSeeder extends Seeder
{
    public function run(): void
    {
        PvTemplate::updateOrCreate(
            ['user_id' => null, 'type' => 'pv'],
            [
                'config' => [
                    'sections' => [
                        [
                            'id' => 'header',
                            'fixed' => true,
                            'title' => 'En-tête du PV',
                            'fields' => ['titre', 'date'],
                            'styles' => [
                                'titleColor' => '#1d4ed8',
                                'titleSize' => 15,
                                'font' => 'DejaVu Sans',
                                'textColor' => '#0f172a',
                                'textSize' => 11,
                            ],
                            'margin' => ['top' => 0, 'bottom' => 10, 'left' => 0, 'right' => 0],
                        ],
                        [
                            'id' => 'participants',
                            'fixed' => false,
                            'title' => 'Participants',
                            'fields' => ['participants'],
                            'styles' => [
                                'titleColor' => '#059669',
                                'titleSize' => 13,
                                'font' => 'DejaVu Sans',
                                'textColor' => '#0f172a',
                                'textSize' => 11,
                            ],
                            'margin' => ['top' => 8, 'bottom' => 8, 'left' => 0, 'right' => 0],
                        ],
                        [
                            'id' => 'contenu',
                            'fixed' => false,
                            'title' => 'Contenu & Délibérations',
                            'fields' => ['contenu'],
                            'styles' => [
                                'titleColor' => '#7c3aed',
                                'titleSize' => 13,
                                'font' => 'DejaVu Sans',
                                'textColor' => '#0f172a',
                                'textSize' => 11,
                            ],
                            'margin' => ['top' => 8, 'bottom' => 8, 'left' => 0, 'right' => 0],
                        ],
                        [
                            'id' => 'signature',
                            'fixed' => true,
                            'title' => 'Clôture & Signatures',
                            'fields' => ['signatures'],
                            'styles' => [
                                'titleColor' => '#64748b',
                                'titleSize' => 12,
                                'font' => 'DejaVu Sans',
                                'textColor' => '#334155',
                                'textSize' => 10,
                            ],
                            'margin' => ['top' => 20, 'bottom' => 0, 'left' => 0, 'right' => 0],
                        ],
                    ],
                ],
                'orientation' => 'portrait',
                'is_default' => true,
                'is_custom' => false,
            ]
        );
    }
}