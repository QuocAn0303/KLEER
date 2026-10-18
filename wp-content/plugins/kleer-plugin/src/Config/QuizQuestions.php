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
 *                 cho phiên bản v1 (legacy 5 câu) và phiên bản v4 (draft 6 câu / 27 lựa chọn),
 *                 kèm danh mục sản phẩm tiêu chuẩn ánh xạ 3 Custom Taxonomies.
 */
final class QuizQuestions
{
    /**
     * Danh sách 5 câu hỏi cốt lõi v1 kèm trọng số điểm chi tiết.
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
     * Danh sách 6 câu hỏi / 27 lựa chọn theo bản nháp v4 (Phụ lục A).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getV4Questions(): array
    {
        return [
            'q1' => [
                'id' => 'q1',
                'title' => 'Sau khi rửa mặt, khoảng 30 phút không bôi gì, da bạn cảm thấy thế nào?',
                'options' => [
                    '1A' => ['label' => 'Căng, khô, có thể bong tróc', 'scores' => ['sensitive' => 1, 'dry' => 3, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                    '1B' => ['label' => 'Thoải mái, không căng cũng không bóng', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 3]],
                    '1C' => ['label' => 'Bóng dầu khắp mặt', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 3, 'normal' => 0]],
                    '1D' => ['label' => 'Bóng ở trán–mũi–cằm, hai má bình thường hoặc hơi khô', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 3, 'oily' => 1, 'normal' => 0]],
                    '1E' => ['label' => 'Châm chích, đỏ hoặc ngứa', 'scores' => ['sensitive' => 3, 'dry' => 1, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                ],
            ],
            'q2' => [
                'id' => 'q2',
                'title' => 'Cuối ngày, vùng chữ T (trán, mũi, cằm) và hai má của bạn trông thế nào?',
                'options' => [
                    '2A' => ['label' => 'Cả mặt bóng dầu, lỗ chân lông to rõ', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 3, 'normal' => 0]],
                    '2B' => ['label' => 'Chữ T bóng, hai má bình thường hoặc khô', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 3, 'oily' => 1, 'normal' => 0]],
                    '2C' => ['label' => 'Cả mặt mịn, không bóng, không khô', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 3]],
                    '2D' => ['label' => 'Xỉn, khô ráp, có vảy nhỏ', 'scores' => ['sensitive' => 0, 'dry' => 3, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                    '2E' => ['label' => 'Hay ửng đỏ, nóng rát dù không làm gì', 'scores' => ['sensitive' => 3, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                ],
            ],
            'q3' => [
                'id' => 'q3',
                'title' => 'Khi đổi sản phẩm, đổi thời tiết hoặc thử hoạt chất mới (BHA, vitamin C…), da bạn phản ứng thế nào?',
                'options' => [
                    '3A' => ['label' => 'Rất hay đỏ, ngứa, rát', 'scores' => ['sensitive' => 4, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                    '3B' => ['label' => 'Thỉnh thoảng hơi đỏ hoặc châm chích nhẹ', 'scores' => ['sensitive' => 2, 'dry' => 1, 'combination' => 0, 'oily' => 0, 'normal' => 0]],
                    '3C' => ['label' => 'Hiếm khi có phản ứng', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 1]],
                    '3D' => ['label' => 'Gần như không bao giờ có phản ứng', 'scores' => ['sensitive' => 0, 'dry' => 0, 'combination' => 0, 'oily' => 0, 'normal' => 2]],
                ],
            ],
            'q4' => [
                'id' => 'q4',
                'title' => 'Điều bạn muốn cải thiện nhất ở làn da hiện tại là gì? (chỉ chọn 1)',
                'options' => [
                    '4A' => ['label' => 'Mụn, mụn ẩn, mụn viêm', 'concern' => 'acne'],
                    '4B' => ['label' => 'Thâm mụn, nám, da không đều màu', 'concern' => 'dark_spots'],
                    '4C' => ['label' => 'Nếp nhăn, da kém đàn hồi', 'concern' => 'aging'],
                    '4D' => ['label' => 'Lỗ chân lông to, da xỉn, sần sùi', 'concern' => 'pores'],
                    '4E' => ['label' => 'Không có vấn đề nổi bật, chỉ muốn duy trì da khỏe', 'concern' => 'none'],
                ],
            ],
            'q5' => [
                'id' => 'q5',
                'title' => 'Bạn muốn routine gồm bao nhiêu sản phẩm (mỗi sản phẩm một bước)?',
                'options' => [
                    '5A' => ['label' => 'Nhanh gọn (2 sản phẩm)', 'routine_steps' => 2],
                    '5B' => ['label' => 'Cơ bản (3 sản phẩm)', 'routine_steps' => 3],
                    '5C' => ['label' => 'Tiêu chuẩn (4 sản phẩm)', 'routine_steps' => 4],
                    '5D' => ['label' => 'Chi tiết (5 sản phẩm)', 'routine_steps' => 5],
                    '5E' => ['label' => 'Đầy đủ (6 sản phẩm)', 'routine_steps' => 6],
                ],
            ],
            'q6' => [
                'id' => 'q6',
                'title' => 'Hằng ngày bạn có trang điểm hoặc dùng kem chống nắng không?',
                'options' => [
                    '6A' => ['label' => 'Có, tôi trang điểm hằng ngày hoặc thường xuyên', 'uses_sunscreen_makeup' => true],
                    '6B' => ['label' => 'Không trang điểm, nhưng có dùng kem chống nắng', 'uses_sunscreen_makeup' => true],
                    '6C' => ['label' => 'Không trang điểm và không dùng kem chống nắng', 'uses_sunscreen_makeup' => false],
                ],
            ],
        ];
    }

    /**
     * Danh mục 20 sản phẩm mặc định (v1 legacy).
     *
     * @return list<array<string, mixed>>
     */
    public static function getDefaultCatalog(): array
    {
        return [
            // --- CLEANSER ---
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
                'skin_types' => ['combination', 'oily', 'normal', 'dry'],
                'concerns' => ['acne', 'hydration', 'pores'],
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

            // --- TREATMENT (Serum) ---
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

            // --- MOISTURIZER / HYDRATION (Hỗ trợ cả step 'toner' và alias 'moisturizer' cho backward compatibility) ---
            [
                'id' => 9,
                'name' => 'Nước Hoa Hồng Klairs Supple Preparation Facial Toner',
                'price' => 300000,
                'routine_step' => 'moisturizer',
                'secondary_step' => 'toner',
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
                'secondary_step' => 'toner',
                'skin_types' => ['combination', 'oily', 'normal'],
                'concerns' => ['acne', 'hydration', 'pores'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'Chiết xuất cây phỉ, Lô hội (Aloe)',
                'reason' => 'Cân bằng độ ẩm, kháng khuẩn và hỗ trợ se khít lỗ chân lông.',
            ],
            [
                'id' => 14,
                'name' => 'Dung Dịch Dưỡng Ẩm Hada Labo Gokujyun Hydrating Lotion',
                'price' => 200000,
                'routine_step' => 'moisturizer',
                'secondary_step' => 'toner',
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
                'secondary_step' => 'toner',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'is_gentle_fallback' => false,
                'active_ingredients' => 'AHA, BHA, PHA, Chiết xuất tràm trà',
                'reason' => 'Kháng viêm, làm sạch tế bào chết và cân bằng dầu nhờn.',
            ],

            // --- SUNSCREEN ---
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

    /**
     * Danh mục đầy đủ 30 SKU chuẩn theo Phụ lục C của bài nộp PO (Phát).
     *
     * @return list<array<string, mixed>>
     */
    public static function getV4Catalog(): array
    {
        return [
            // 1..8: Cleanser (Sữa rửa mặt)
            [
                'id' => 1,
                'stt' => 1,
                'name' => 'Sữa Rửa Mặt CeraVe Foaming Cleanser',
                'routine_step' => 'cleanser',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'has_strong_actives' => false,
                'price' => 380000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Làm sạch sâu bã nhờn, kiềm dầu thừa hiệu quả.',
            ],
            [
                'id' => 2,
                'stt' => 2,
                'name' => 'Sữa Rửa Mặt CeraVe Hydrating Cleanser',
                'routine_step' => 'cleanser',
                'skin_types' => ['sensitive', 'dry'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 380000,
                'skin_description' => 'Khô, Nhạy cảm',
                'reason' => 'Làm sạch dịu nhẹ không tạo bọt, cấp ẩm sâu không gây khô căng.',
            ],
            [
                'id' => 3,
                'stt' => 3,
                'name' => 'Sữa Rửa Mặt Cetaphil Gentle Skin Cleanser',
                'routine_step' => 'cleanser',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 330000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Công thức dịu lành chuẩn y khoa, an toàn cho mọi làn da.',
            ],
            [
                'id' => 4,
                'stt' => 4,
                'name' => 'Gel Rửa Mặt La Roche-Posay Effaclar',
                'routine_step' => 'cleanser',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'has_strong_actives' => false,
                'price' => 450000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Giảm sưng viêm mụn, kiểm soát bã nhờn tiết ra.',
            ],
            [
                'id' => 5,
                'stt' => 5,
                'name' => 'Sữa Rửa Mặt Cosrx Low pH Good Morning',
                'routine_step' => 'cleanser',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => ['pores'],
                'has_strong_actives' => true,
                'price' => 250000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Tẩy tế bào chết nhẹ dịu với độ pH chuẩn 5.5.',
            ],
            [
                'id' => 6,
                'stt' => 6,
                'name' => 'Sữa Rửa Mặt Simple Refreshing Facial Wash',
                'routine_step' => 'cleanser',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 150000,
                'skin_description' => 'Nhạy cảm',
                'reason' => '100% không chứa xà phòng, làm dịu da nhạy cảm tức thì.',
            ],
            [
                'id' => 7,
                'stt' => 7,
                'name' => 'Gel Rửa Mặt SVR Sebiaclear Moussant',
                'routine_step' => 'cleanser',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'has_strong_actives' => false,
                'price' => 400000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Làm sạch sâu bít tắc, giảm sưng viêm.',
            ],
            [
                'id' => 8,
                'stt' => 8,
                'name' => 'Sữa Rửa Mặt Innisfree Green Tea',
                'routine_step' => 'cleanser',
                'skin_types' => ['combination'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 220000,
                'skin_description' => 'Hỗn hợp',
                'reason' => 'Làm sạch bã nhờn, chống oxy hóa với chiết xuất trà xanh.',
            ],

            // 9..15: Toner
            [
                'id' => 9,
                'stt' => 9,
                'name' => 'Nước Hoa Hồng Klairs Supple Preparation',
                'routine_step' => 'toner',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 300000,
                'skin_description' => 'Nhạy cảm',
                'reason' => 'Cấp ẩm sâu tức thì, làm dịu vùng da mẩn đỏ.',
            ],
            [
                'id' => 10,
                'stt' => 10,
                'name' => 'Toner Thayers Witch Hazel Aloe Vera',
                'routine_step' => 'toner',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => ['pores'],
                'has_strong_actives' => false,
                'price' => 250000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Cân bằng pH, hỗ trợ se khít lỗ chân lông dịu nhẹ.',
            ],
            [
                'id' => 11,
                'stt' => 11,
                'name' => 'Dung Dịch Tẩy Da Chết Paula\'s Choice 2% BHA',
                'routine_step' => 'toner',
                'skin_types' => ['oily'],
                'concerns' => ['acne', 'pores'],
                'has_strong_actives' => true,
                'price' => 850000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Đẩy mụn ẩn, thu nhỏ lỗ chân lông với 2% Salicylic Acid.',
            ],
            [
                'id' => 12,
                'stt' => 12,
                'name' => 'Nước Cân Bằng COSRX AHA/BHA',
                'routine_step' => 'toner',
                'skin_types' => ['combination'],
                'concerns' => ['pores'],
                'has_strong_actives' => true,
                'price' => 320000,
                'skin_description' => 'Hỗn hợp',
                'reason' => 'Tẩy tế bào chết bề mặt, giảm sần sùi cho da hỗn hợp.',
            ],
            [
                'id' => 13,
                'stt' => 13,
                'name' => 'Nước Hoa Hồng Mamonde Rose Water',
                'routine_step' => 'toner',
                'skin_types' => ['dry'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 280000,
                'skin_description' => 'Khô',
                'reason' => 'Cấp ẩm, làm dịu tức thì màng ẩm với tinh chất hoa hồng.',
            ],
            [
                'id' => 14,
                'stt' => 14,
                'name' => 'Toner Hada Labo Gokujyun Hydrating',
                'routine_step' => 'toner',
                'skin_types' => ['dry'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 200000,
                'skin_description' => 'Khô',
                'reason' => 'Cấp nước đa tầng, giúp da khô căng mọng.',
            ],
            [
                'id' => 15,
                'stt' => 15,
                'name' => 'Toner Some By Mi AHA-BHA-PHA 30 Days',
                'routine_step' => 'toner',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'has_strong_actives' => true,
                'price' => 350000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Kháng viêm, làm sạch tế bào chết và cân bằng dầu nhờn.',
            ],

            // 16..23: Serum
            [
                'id' => 16,
                'stt' => 16,
                'name' => 'Tinh Chất The Ordinary Niacinamide 10%',
                'routine_step' => 'serum',
                'skin_types' => ['oily'],
                'concerns' => ['acne', 'dark_spots'],
                'has_strong_actives' => false,
                'price' => 250000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Kiềm dầu vượt trội, làm mờ vết thâm mụn.',
            ],
            [
                'id' => 17,
                'stt' => 17,
                'name' => 'Tinh Chất La Roche-Posay Hyalu B5',
                'routine_step' => 'serum',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 850000,
                'skin_description' => 'Nhạy cảm',
                'reason' => 'Phục hồi da tổn thương, cấp ẩm sâu với B5.',
            ],
            [
                'id' => 18,
                'stt' => 18,
                'name' => 'Dưỡng Chất Vichy Mineral 89',
                'routine_step' => 'serum',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 650000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Củng cố hàng rào bảo vệ, cấp khoáng núi lửa cho da.',
            ],
            [
                'id' => 19,
                'stt' => 19,
                'name' => 'Tinh Chất Klairs Freshly Juiced Vitamin Drop',
                'routine_step' => 'serum',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => ['dark_spots'],
                'has_strong_actives' => true,
                'price' => 350000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Mờ thâm mụn, làm đều màu da với 5% Vitamin C tươi.',
            ],
            [
                'id' => 20,
                'stt' => 20,
                'name' => 'Tinh Chất L\'Oreal Revitalift 1.5% HA',
                'routine_step' => 'serum',
                'skin_types' => ['dry'],
                'concerns' => ['aging'],
                'has_strong_actives' => false,
                'price' => 450000,
                'skin_description' => 'Khô, Lão hóa',
                'reason' => 'Cấp ẩm làm căng bóng, hỗ trợ giảm rãnh nhăn.',
            ],
            [
                'id' => 21,
                'stt' => 21,
                'name' => 'Tinh Chất Skin1004 Madagascar Centella',
                'routine_step' => 'serum',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 400000,
                'skin_description' => 'Nhạy cảm',
                'reason' => 'Kháng viêm, làm dịu da kích ứng đỏ rát nhanh chóng.',
            ],
            [
                'id' => 22,
                'stt' => 22,
                'name' => 'Tinh Chất Kiehl\'s Clearly Corrective',
                'routine_step' => 'serum',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => ['dark_spots'],
                'has_strong_actives' => true,
                'price' => 1500000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Trị thâm nám, dưỡng trắng sáng da chuyên sâu.',
            ],
            [
                'id' => 23,
                'stt' => 23,
                'name' => 'Tinh Chất balance Active Formula Vitamin C',
                'routine_step' => 'serum',
                'skin_types' => ['dry', 'combination'],
                'concerns' => ['dark_spots'],
                'has_strong_actives' => true,
                'price' => 180000,
                'skin_description' => 'Khô, Hỗn hợp',
                'reason' => 'Làm sáng da, mờ thâm hiệu quả kinh tế.',
            ],

            // 24..30: Sunscreen (Chống nắng)
            [
                'id' => 24,
                'stt' => 24,
                'name' => 'Kem Chống Nắng La Roche-Posay Anthelios',
                'routine_step' => 'sunscreen',
                'skin_types' => ['oily'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 450000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Chống nắng quang phổ rộng, kiểm soát bóng dầu tối đa.',
            ],
            [
                'id' => 25,
                'stt' => 25,
                'name' => 'Sữa Chống Nắng Anessa Perfect UV',
                'routine_step' => 'sunscreen',
                'skin_types' => ['sensitive', 'dry', 'combination', 'oily', 'normal', 'all_skin_types'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 550000,
                'skin_description' => 'Mọi loại da',
                'reason' => 'Chống UV tối ưu với công nghệ chống trôi vượt trội.',
            ],
            [
                'id' => 26,
                'stt' => 26,
                'name' => 'Tinh Chất Chống Nắng Skin Aqua Tone Up',
                'routine_step' => 'sunscreen',
                'skin_types' => ['combination'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 180000,
                'skin_description' => 'Hỗn hợp',
                'reason' => 'Nâng tông da hồng hào tự nhiên, chống nắng bảo vệ.',
            ],
            [
                'id' => 27,
                'stt' => 27,
                'name' => 'Kem Chống Nắng Cell Fusion C Laser',
                'routine_step' => 'sunscreen',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 600000,
                'skin_description' => 'Nhạy cảm',
                'reason' => 'Bảo vệ và phục hồi da sau treatment.',
            ],
            [
                'id' => 28,
                'stt' => 28,
                'name' => 'Gel Chống Nắng Eucerin Sun Dry Touch',
                'routine_step' => 'sunscreen',
                'skin_types' => ['oily'],
                'concerns' => ['acne'],
                'has_strong_actives' => false,
                'price' => 500000,
                'skin_description' => 'Dầu, Mụn',
                'reason' => 'Kiểm soát bóng nhờn, bảo vệ ngăn ngừa sinh mụn.',
            ],
            [
                'id' => 29,
                'stt' => 29,
                'name' => 'Kem Chống Nắng Vichy Capital Soleil',
                'routine_step' => 'sunscreen',
                'skin_types' => ['combination'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 480000,
                'skin_description' => 'Hỗn hợp',
                'reason' => 'Bảo vệ da khỏi tia UV và bụi mịn ô nhiễm.',
            ],
            [
                'id' => 30,
                'stt' => 30,
                'name' => 'Kem Chống Nắng Skin1004 Centella Air-Fit',
                'routine_step' => 'sunscreen',
                'skin_types' => ['sensitive'],
                'concerns' => [],
                'has_strong_actives' => false,
                'price' => 350000,
                'skin_description' => 'Nhạy cảm',
                'reason' => 'Chống nắng thuần vật lý, nâng tông tự nhiên và làm dịu da.',
            ],
        ];
    }
}
