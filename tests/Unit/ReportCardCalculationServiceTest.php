<?php

namespace Tests\Unit;

use App\Services\Schools\ReportCardCalculationService;
use PHPUnit\Framework\TestCase;

class ReportCardCalculationServiceTest extends TestCase
{
    public function test_assigns_competition_positions_and_preserves_ties(): void
    {
        $positions = (new ReportCardCalculationService)->positionsByScore(collect([
            10 => 75,
            20 => 92,
            30 => 75,
            40 => 60,
        ]));

        $this->assertSame([
            20 => 1,
            10 => 2,
            30 => 2,
            40 => 4,
        ], $positions->all());
    }

    public function test_calculates_a_direct_weighted_subject_score(): void
    {
        $result = (new ReportCardCalculationService)->calculateSubjectScore(
            collect([
                ['id' => 1, 'raw_max_score' => 20, 'weight' => 20],
                ['id' => 2, 'raw_max_score' => 30, 'weight' => 30],
                ['id' => 3, 'raw_max_score' => 50, 'weight' => 50],
            ]),
            collect([
                ['assessment_item_id' => 1, 'score' => 16],
                ['assessment_item_id' => 2, 'score' => 24],
                ['assessment_item_id' => 3, 'score' => 40],
            ]),
        );

        $this->assertSame(80.0, $result['score']);
        $this->assertSame(100.0, $result['maximum_score']);
        $this->assertSame(16.0, $result['assessment_items'][0]['raw_score']);
        $this->assertSame(16.0, $result['assessment_items'][0]['weighted_score']);
    }

    public function test_converts_a_large_raw_score_to_its_report_weight(): void
    {
        $result = (new ReportCardCalculationService)->calculateSubjectScore(
            collect([
                ['id' => 1, 'raw_max_score' => 500, 'weight' => 10],
                ['id' => 2, 'raw_max_score' => 100, 'weight' => 90],
            ]),
            collect([
                ['assessment_item_id' => 1, 'score' => 425],
                ['assessment_item_id' => 2, 'score' => 72],
            ]),
        );

        $this->assertSame(73.3, $result['score']);
        $this->assertSame(100.0, $result['maximum_score']);
        $this->assertSame(8.5, $result['assessment_items'][0]['weighted_score']);
    }
}
