<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductSampleExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'sku', 'brand', 'price', 'sale_price', 'on_sale',
            'category', 'stock', 'colors',
            'is_active', 'is_featured',
            'title_ka', 'title_en',
            'excerpt_ka', 'excerpt_en',
            'content_ka', 'content_en',
        ];
    }

    public function array(): array
    {
        return [
            [
                'TV-STAND-001', 'Noblesse', 550.00, 420.00, 1,
                'საძინებლის ავეჯი', 5, 'ყავისფერი,კაკალი',
                1, 1,
                'TV საყრდენი მაგიდა მარმარილოთი', 'TV Stand with Marble Top',
                'კლასიკური სტილის TV საყრდენი', 'Classic style TV stand',
                '', '',
            ],
            [
                'SOFA-GR-001', 'HomeStyle', 1200.00, '', 0,
                'სასტუმრო ოთახის ავეჯი', 3, 'მუქი ნაცრისფერი',
                1, 0,
                'რბილი დივანი ნაცრისფერი', 'Soft Sofa Grey',
                'კომფორტული სამადგილიანი დივანი', 'Comfortable 3-seat sofa',
                '', '',
            ],
            [
                'TABLE-RND-001', 'Noblesse', 380.00, 320.00, 1,
                'სასტუმრო ოთახის ავეჯი', 8, 'ყავისფერი',
                1, 0,
                'მრგვალი ყავის მაგიდა', 'Round Coffee Table',
                'მასიური ხის მრგვალი ყავის მაგიდა', 'Solid wood round coffee table',
                '', '',
            ],
            [
                'BED-180-001', 'DreamFurn', 2100.00, '', 0,
                'საძინებლის ავეჯი', 2, 'თეთრი,ბეჟი',
                1, 1,
                'საწოლი 180x200', 'Bed 180x200',
                'ორმაგი საწოლი ნაგულვე ბოქს-სარეცელით', 'Double bed with storage box-spring',
                '', '',
            ],
            [
                'CHAIR-OFF-001', 'ErgoPlus', 450.00, 390.00, 1,
                'საოფისე ავეჯი', 15, 'შავი',
                1, 0,
                'სამეთვალყურეო სკამი', 'Office Chair',
                'რეგულირებადი სიმაღლის ერგონომიული სკამი', 'Ergonomic adjustable height office chair',
                '', '',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F2E47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 14, 'C' => 10, 'D' => 12, 'E' => 10,
            'F' => 28, 'G' => 8,  'H' => 22,
            'I' => 11, 'J' => 13,
            'K' => 35, 'L' => 35,
            'M' => 35, 'N' => 35,
            'O' => 20, 'P' => 20,
        ];
    }
}
