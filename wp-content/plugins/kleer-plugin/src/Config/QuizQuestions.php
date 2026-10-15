<?php

declare(strict_types=1);

namespace Kleer\Config;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Quiz Questions and Taxonomy Scoring Configuration.
 *
 * Layer: Config / Domain Reference Data
 * Responsibility: Cung cấp cấu trúc câu hỏi, ma trận gán trọng số điểm (Weighted Tag Matching)
 *                 và danh mục sản phẩm tiêu chuẩn ánh xạ 3 Custom Taxonomies.
 */
final class QuizQuestions
{
    /**
     * Danh sách 5 câu hỏi cốt lõi kèm trọng số điểm chi tiết.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getQuestions(): array
    {
        return [
            'q1_skin_feeling' => [
                'id' => 'q1_skin_feeling',
                'title' => 'Cảm giác bề mặt da sau khi rửa mặt 30 phút mà chưa thoa gì?',
                'options' => [
                    'tight_dry' => [
                        'label' => 'Căng rát, khô khốc khó chịu',
                        'weights' => ['skin_type' => ['dry' => 3]],
                    ],
                    'greasy_all' => [
                        'label' => 'Bóng nhờn toàn bộ khuôn mặt',
                        'weights' => ['skin_type' => ['oily' => 3]],
                    ],
                    'oily_tzone' => [
                        'label' => 'Nhờn vùng chữ T (trán, mũi, cằm), khô hai bên má',
                        'weights' => ['skin_type' => ['combination' => 3]],
                    ],
                    'comfortable' => [
                        'label' => 'Mềm mại, dễ chịu, không khô cũng không nhờn',
                        'weights' => ['skin_type' => ['normal' => 3]],
                    ],
                ],
            ],
            'q2_pore_sebum' => [
                'id' => 'q2_pore_sebum',
                'title' => 'Tình trạng lỗ chân lông và dầu thừa của bạn như thế nào?',
                'options' => [
                    'large_pores' => [
                        'label' => 'Lỗ chân lông to, nhanh đổ dầu sau vài giờ',
                        'weights' => [
                            'skin_type' => ['oily' => 2],
                            'concerns' => ['acne' => 1],
                        ],
                    ],
                    'normal_pores' => [
                        'label' => 'Lỗ chân lông nhỏ, ít thấy dầu thừa',
                        'weights' => [
                            'skin_type' => ['normal' => 2, 'dry' => 1],
                        ],
                    ],
                    'tzone_only' => [
                        'label' => 'Chỉ thấy rõ lỗ chân lông và dầu ở trán/mũi',
                        'weights' => [
                            'skin_type' => ['combination' => 2],
                        ],
                    ],
                    'flaky_rough' => [
                        'label' => 'Da sần sùi, thường xuyên bong tróc mảng nhỏ',
                        'weights' => [
                            'skin_type' => ['dry' => 3],
                            'concerns' => ['hydration' => 1],
                        ],
                    ],
                ],
            ],
            'q3_sensitivity' => [
                'id' => 'q3_sensitivity',
                'title' => 'Da bạn có dễ bị đỏ rát, châm chích khi đổi thời tiết hoặc dùng mỹ phẩm mới?',
                'options' => [
                    'very_often' => [
                        'label' => 'Rất thường xuyên đỏ rát, mẩn ngứa',
                        'weights' => ['sensitivity' => 4],
                    ],
                    'sometimes' => [
                        'label' => 'Thỉnh thoảng khi tiếp xúc hóa chất lạ',
                        'weights' => ['sensitivity' => 2],
                    ],
                    'rarely_never' => [
                        'label' => 'Rất hiếm hoặc chưa từng kích ứng',
                        'weights' => ['sensitivity' => 0],
                    ],
                ],
            ],
            'q4_main_concern' => [
                'id' => 'q4_main_concern',
                'title' => 'Vấn đề về da bạn muốn tập trung cải thiện nhất trong 4-8 tuần tới?',
                'options' => [
                    'acne_blemish' => [
                        'label' => 'Mụn bọc, mụn ẩn, mụn đầu đen, bít tắc',
                        'weights' => ['concerns' => ['acne' => 4]],
                    ],
                    'dark_spots' => [
                        'label' => 'Vết thâm mụn, da không đều màu, sạm xỉn',
                        'weights' => ['concerns' => ['dark_spots' => 4]],
                    ],
                    'dehydration' => [
                        'label' => 'Da thiếu nước, thiếu ẩm, khô sạm thiếu sức sống',
                        'weights' => ['concerns' => ['hydration' => 4]],
                    ],
                    'anti_aging' => [
                        'label' => 'Nếp nhăn li ti, da kém săn chắc, lão hóa',
                        'weights' => ['concerns' => ['aging' => 4]],
                    ],
                ],
            ],
            'q5_routine_goal' => [
                'id' => 'q5_routine_goal',
                'title' => 'Phong cách Routine chăm sóc da mong muốn của bạn?',
                'options' => [
                    'minimal' => [
                        'label' => 'Tối giản 3 bước nhanh gọn (Làm sạch - Dưỡng ẩm - Chống nắng)',
                        'weights' => ['routine_steps' => 3],
                    ],
                    'intensive' => [
                        'label' => 'Chuyên sâu 4 bước hiệu quả cao (thêm bước Serum đặc trị)',
                        'weights' => ['routine_steps' => 4],
                    ],
                ],
            ],
        ];
    }

    /**
     * Danh mục 30 sản phẩm tiêu chuẩn của KLEER (ánh xạ từ Task08_30_Skincare.csv)
     * làm dữ liệu mặc định và dữ liệu kiểm thử.
     *
     * @return list<array<string, mixed>>
     */
    public static function getDefaultCatalog(): array
    {
        return [
            // --- CLEANSER (Bước 1: Làm sạch) ---
            [
                'id' => 1,
                'name' => 'Sữa Rửa Mặt CeraVe Foaming Cleanser',
                'price' => 380000,
                'routine_step' => 'cleanser',
                'skin_types' => ['oily', 'combination'],
                'concerns' => ['acne'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Ceramide, Niacinamide, Hyaluronic Acid',
                'reason' => 'Làm sạch sâu, kiềm dầu thừa, phục hồi màng bảo vệ tự nhiên.',
            ],
            [
                'id' => 2,
                'name' => 'Sữa Rửa Mặt CeraVe Hydrating Cleanser',
                'price' => 380000,
                'routine_step' => 'cleanser',
                'skin_types' => ['dry', 'sensitive'],
                'concerns' => ['hydration'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Ceramide, Hyaluronic Acid, Glycerin',
                'reason' => 'Làm sạch dịu nhẹ không tạo bọt, cấp ẩm sâu không gây khô căng.',
            ],
            [
                'id' => 3,
                'name' => 'Sữa Rửa Mặt Cetaphil Gentle Skin Cleanser',
                'price' => 330000,
                'routine_step' => 'cleanser',
                'skin_types' => ['all_skin_types', 'sensitive', 'normal', 'dry', 'oily', 'combination'],
                'concerns' => ['soothing', 'hydration'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Niacinamide, Panthenol, Glycerin',
                'reason' => 'Công thức dịu lành chuẩn y khoa, an toàn cho mọi làn da nhạy cảm.',
            ],
            [
                'id' => 4,
                'name' => 'Gel Rửa Mặt La Roche-Posay Effaclar',
                'price' => 450000,
                'routine_step' => 'cleanser',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Zinc PCA, Nước khoáng LRP',
                'reason' => 'Giảm sưng viêm mụn, kiểm soát bã nhờn hiệu quả.',
            ],
            [
                'id' => 5,
                'name' => 'Sữa Rửa Mặt Cosrx Low pH Good Morning',
                'price' => 250000,
                'routine_step' => 'cleanser',
                'skin_types' => ['combination', 'oily'],
                'concerns' => ['acne', 'hydration'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'BHA, Tinh dầu tràm trà (Tea Tree)',
                'reason' => 'Tẩy tế bào chết nhẹ dịu với độ pH chuẩn 5.5.',
            ],
            [
                'id' => 6,
                'name' => 'Sữa Rửa Mặt Simple Refreshing Facial Wash',
                'price' => 150000,
                'routine_step' => 'cleanser',
                'skin_types' => ['sensitive', 'dry', 'all_skin_types'],
                'concerns' => ['soothing'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Pro-Vitamin B5, Vitamin E',
                'reason' => '100% không chứa xà phòng, làm dịu da nhạy cảm tức thì.',
            ],

            // --- TREATMENT (Bước 2: Đặc trị / Serum) ---
            [
                'id' => 16,
                'name' => 'Tinh Chất The Ordinary Niacinamide 10% + Zinc 1%',
                'price' => 250000,
                'routine_step' => 'treatment',
                'skin_types' => ['oily', 'combination'],
                'concerns' => ['acne', 'dark_spots'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Niacinamide 10%, Zinc 1%',
                'reason' => 'Kiềm dầu vượt trội, làm mờ vết thâm mụn và thu nhỏ lỗ chân lông.',
            ],
            [
                'id' => 17,
                'name' => 'Tinh Chất La Roche-Posay Hyalu B5',
                'price' => 850000,
                'routine_step' => 'treatment',
                'skin_types' => ['dry', 'sensitive', 'normal'],
                'concerns' => ['hydration', 'aging'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Hyaluronic Acid 2 kích thước, Vitamin B5',
                'reason' => 'Phục hồi màng ẩm chuyên sâu, làm căng bóng và tái sinh làn da mỏng yếu.',
            ],
            [
                'id' => 18,
                'name' => 'Dưỡng Chất Vichy Mineral 89 Booster',
                'price' => 650000,
                'routine_step' => 'treatment',
                'skin_types' => ['all_skin_types', 'sensitive', 'dry', 'oily', 'combination', 'normal'],
                'concerns' => ['hydration', 'soothing'],
                'is_gentle_fallback' => true,
                'active_ingredients' => '89% Nước khoáng núi lửa, Hyaluronic Acid',
                'reason' => 'Củng cố hàng rào bảo vệ da, cấp nước đa tầng an toàn cho mọi làn da.',
            ],
            [
                'id' => 19,
                'name' => 'Tinh Chất Klairs Freshly Juiced Vitamin Drop',
                'price' => 350000,
                'routine_step' => 'treatment',
                'skin_types' => ['all_skin_types', 'combination', 'normal'],
                'concerns' => ['dark_spots'],
                'is_gentle_fallback' => false,
                'active_ingredients' => '5% Vitamin C tươi, Centella Asiatica',
                'reason' => 'Làm đều màu da, mờ thâm mụn nhẹ nhàng không gây châm chích.',
            ],
            [
                'id' => 20,
                'name' => 'Tinh Chất L\'Oreal Revitalift 1.5% Pure Hyaluronic Acid',
                'price' => 450000,
                'routine_step' => 'treatment',
                'skin_types' => ['dry', 'normal'],
                'concerns' => ['aging', 'hydration'],
                'is_gentle_fallback' => false,
                'active_ingredients' => '1.5% Hyaluronic Acid nguyên chất',
                'reason' => 'Làm đầy các rãnh nhăn li ti, giúp da căng mọng đàn hồi.',
            ],
            [
                'id' => 21,
                'name' => 'Tinh Chất Skin1004 Madagascar Centella Ampoule',
                'price' => 400000,
                'routine_step' => 'treatment',
                'skin_types' => ['sensitive', 'acne', 'all_skin_types'],
                'concerns' => ['soothing', 'acne'],
                'is_gentle_fallback' => true,
                'active_ingredients' => '100% Chiết xuất rau má Madagascar',
                'reason' => 'Kháng viêm, làm dịu da kích ứng đỏ rát nhanh chóng.',
            ],

            // --- MOISTURIZER / HYDRATION (Bước 3: Dưỡng ẩm / Cân bằng) ---
            [
                'id' => 9,
                'name' => 'Nước Hoa Hồng Klairs Supple Preparation Facial Toner',
                'price' => 300000,
                'routine_step' => 'moisturizer',
                'skin_types' => ['sensitive', 'dry', 'normal'],
                'concerns' => ['soothing', 'hydration'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Phyto-Oligo, Axit Amin lúa mì, Centella',
                'reason' => 'Cân bằng độ ẩm sâu tức thì, làm dịu vùng da ửng đỏ.',
            ],
            [
                'id' => 10,
                'name' => 'Toner Thayers Witch Hazel Aloe Vera',
                'price' => 250000,
                'routine_step' => 'moisturizer',
                'skin_types' => ['combination', 'oily', 'normal'],
                'concerns' => ['acne', 'hydration'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Chiết xuất cây phỉ, Lô hội (Aloe)',
                'reason' => 'Cân bằng độ ẩm, kháng khuẩn và hỗ trợ se khít lỗ chân lông.',
            ],
            [
                'id' => 14,
                'name' => 'Dung Dịch Dưỡng Ẩm Hada Labo Gokujyun Hydrating Lotion',
                'price' => 200000,
                'routine_step' => 'moisturizer',
                'skin_types' => ['dry', 'dehydrated', 'all_skin_types'],
                'concerns' => ['hydration', 'aging'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Super Hyaluronic Acid đa tầng',
                'reason' => 'Cấp nước vượt trội, duy trì độ ẩm suốt 24 giờ.',
            ],
            [
                'id' => 15,
                'name' => 'Toner Some By Mi AHA-BHA-PHA 30 Days Miracle',
                'price' => 350000,
                'routine_step' => 'moisturizer',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'AHA, BHA, PHA, Chiết xuất tràm trà',
                'reason' => 'Kháng viêm, làm sạch tế bào chết và cân bằng dầu nhờn.',
            ],

            // --- SUNSCREEN (Bước 4: Chống nắng bảo vệ) ---
            [
                'id' => 24,
                'name' => 'Kem Chống Nắng La Roche-Posay Anthelios Dry Touch',
                'price' => 450000,
                'routine_step' => 'sunscreen',
                'skin_types' => ['oily', 'combination'],
                'concerns' => ['acne', 'dark_spots'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Màng lọc Mexoryl XL, Nước khoáng LRP',
                'reason' => 'Màng lọc quang phổ rộng, kiềm dầu khô thoáng đến 8 tiếng.',
            ],
            [
                'id' => 25,
                'name' => 'Sữa Chống Nắng Anessa Perfect UV Sunscreen Skincare Milk',
                'price' => 550000,
                'routine_step' => 'sunscreen',
                'skin_types' => ['all_skin_types', 'normal', 'combination'],
                'concerns' => ['dark_spots', 'aging'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Zinc Oxide, Titanium Dioxide, Collagen',
                'reason' => 'Chống tia UVA/UVB tối đa với công nghệ chống trôi vượt trội.',
            ],
            [
                'id' => 27,
                'name' => 'Kem Chống Nắng Cell Fusion C Laser Sunscreen 100',
                'price' => 600000,
                'routine_step' => 'sunscreen',
                'skin_types' => ['sensitive', 'dry', 'all_skin_types'],
                'concerns' => ['soothing', 'aging'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Zinc Oxide, Collagen, Peptide',
                'reason' => 'Bảo vệ màng ẩm dịu nhẹ, tái tạo tế bào sau treatment.',
            ],
            [
                'id' => 30,
                'name' => 'Kem Chống Nắng Skin1004 Madagascar Centella Air-Fit Suncream',
                'price' => 350000,
                'routine_step' => 'sunscreen',
                'skin_types' => ['sensitive', 'acne', 'combination', 'all_skin_types'],
                'concerns' => ['soothing', 'acne'],
                'is_gentle_fallback' => true,
                'active_ingredients' => 'Zinc Oxide, Chiết xuất rau má Centella',
                'reason' => 'Chống nắng thuần vật lý, nâng tông tự nhiên và làm dịu da mụn.',
            ],
        ];
    }
}
