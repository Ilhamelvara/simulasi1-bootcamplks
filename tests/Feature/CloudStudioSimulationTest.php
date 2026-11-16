<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Developer;
use App\Models\Auditor;
use App\Models\Validation;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionFieldValue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CloudStudioSimulationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan password akun default terpasang
        Auditor::where('username', 'admin_auditor')->update(['password' => Hash::make('AuditorPassword123!')]);
        Developer::where('username', 'alexm')->update(['password' => Hash::make('SecurePassword123!')]);
    }

    /** A1: Authentication **/

    public function test_a1a_register_success()
    {
        Developer::where('username', 'alexm_new')->delete();

        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Alex Mercer',
            'username'  => 'alexm_new',
            'password'  => 'SecurePassword123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'full_name', 'username', 'role'],
                'token',
            ])
            ->assertJson([
                'message' => 'Registration successful',
                'user' => [
                    'username' => 'alexm_new',
                    'role'     => 'developer',
                ],
            ]);
    }

    public function test_a1b_register_validation_failure()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Alex Mercer',
            'username'  => 'alexm', // sudah ada
            'password'  => '123',   // min:6
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid field',
            ])
            ->assertJsonStructure([
                'message',
                'errors' => ['username', 'password'],
            ]);
    }

    public function test_a1c_login_success_auditor()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_auditor',
            'password' => 'AuditorPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'username', 'role'],
                'token',
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'username' => 'admin_auditor',
                    'role'     => 'auditor',
                ],
            ]);
    }

    public function test_a1d_login_incorrect_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin_auditor',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Username or password incorrect',
            ]);
    }

    public function test_a1e_logout_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout success',
            ]);
    }

    public function test_a1f_logout_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_or_expired_token')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized user',
            ]);
    }

    /** A2: Data Validation Gate **/

    public function test_a2a_request_data_validation_success()
    {
        $developer = Developer::where('username', 'marcusw')->first();
        Validation::where('developer_id', $developer->id)->delete();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/validation', [
                'github_url'     => 'https://github.com/alexmercer',
                'tech_stack'     => 'Kubernetes, Go, Rust, Terraform',
                'monthly_income' => 4500.0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Request data validation sent successful',
            ]);
    }

    public function test_a2b_request_data_validation_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->postJson('/api/v1/validation', [
                'github_url'     => 'https://github.com/alexmercer',
                'tech_stack'     => 'Kubernetes, Go',
                'monthly_income' => 4500.0,
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized user',
            ]);
    }

    public function test_a2c_request_data_validation_invalid_fields()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/validation', [
                'github_url'     => 'invalid-url',
                'tech_stack'     => '',
                'monthly_income' => 'four thousand',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid field',
            ])
            ->assertJsonStructure([
                'message',
                'errors' => ['github_url', 'tech_stack', 'monthly_income'],
            ]);
    }

    public function test_a2d_request_data_validation_already_submitted()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/validation', [
                'github_url'     => 'https://github.com/alexmercer',
                'tech_stack'     => 'Kubernetes, Go',
                'monthly_income' => 4500.0,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'A validation request has already been submitted and is either pending or already approved',
            ]);
    }

    public function test_a2e_get_validation_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/validations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'validation' => [
                    'id',
                    'status',
                    'github_url',
                    'tech_stack',
                    'monthly_income',
                    'auditor_notes',
                    'auditor',
                ]
            ]);
    }

    public function test_a2f_get_validation_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/v1/validations');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized user',
            ]);
    }

    public function test_a2g_auditor_review_success()
    {
        $auditor = Auditor::where('username', 'admin_auditor')->first();
        $token = $auditor->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/validations/1/review', [
                'status'        => 'accepted',
                'auditor_notes' => 'All requirements verified and approved.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Validation status updated successfully',
            ]);
    }

    public function test_a2h_auditor_review_forbidden_non_auditor()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/validations/1/review', [
                'status' => 'accepted',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Auditor access required',
            ]);
    }

    /** A3: Infrastructure Page Builder **/

    public function test_a3a_create_page_success()
    {
        Page::where('slug', 'test-edge-gateway')->delete();
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/pages', [
                'title' => 'Test Gateway Cluster',
                'slug'  => 'test-edge-gateway',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'page' => ['id', 'title', 'slug', 'is_published'],
            ])
            ->assertJson([
                'message' => 'Page created successfully',
                'page' => [
                    'slug'         => 'test-edge-gateway',
                    'is_published' => false,
                ],
            ]);
    }

    public function test_a3b_create_page_duplicate_slug()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/pages', [
                'title' => 'Edge Gateway Cluster Duplicate',
                'slug'  => 'edge-gateway-cluster', // sudah ada
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid field',
            ])
            ->assertJsonStructure([
                'message',
                'errors' => ['slug'],
            ]);
    }

    public function test_a3c_append_section_with_auto_reordering_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/pages/edge-gateway-cluster/sections', [
                'template_id' => 3,
                'position'    => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'section' => ['id', 'template_id', 'position'],
            ])
            ->assertJson([
                'message' => 'Section appended successfully',
                'section' => [
                    'template_id' => 3,
                    'position'    => 1,
                ],
            ]);
    }

    public function test_a3d_append_section_page_not_found()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/pages/non-existent-page-slug/sections', [
                'template_id' => 3,
                'position'    => 1,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Page not found',
            ]);
    }

    public function test_a3e_update_section_fields_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $section = PageSection::whereHas('page', function ($q) {
            $q->where('slug', 'edge-gateway-cluster');
        })->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/pages/edge-gateway-cluster/sections/' . $section->id . '/fields', [
                'fields' => [
                    [
                        'template_field_id' => 12,
                        'value'             => '10.0.0.0/16 Virtual Private Cloud',
                    ],
                    [
                        'template_field_id' => 13,
                        'value'             => 'Dual Load Balancer Active-Passive',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Fields persisted successfully',
            ]);
    }

    public function test_a3f_update_section_fields_not_found()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/pages/edge-gateway-cluster/sections/99999/fields', [
                'fields' => [
                    [
                        'template_field_id' => 12,
                        'value'             => '10.0.0.0/16',
                    ],
                ],
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Section not found',
            ]);
    }

    public function test_a3g_publish_infrastructure_page_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/pages/edge-gateway-cluster/publish');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Page published successfully',
            ]);
    }

    public function test_a3h_publish_infrastructure_page_forbidden_unverified()
    {
        $developer = Developer::where('username', 'sarahc')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        // Buat page untuk sarahc yang status validasinya masih pending
        $page = Page::updateOrCreate(
            ['slug' => 'sarah-pending-page'],
            [
                'developer_id' => $developer->id,
                'title'        => 'Sarah Pending Page',
                'is_published' => false,
            ]
        );

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/pages/sarah-pending-page/publish');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Developer profile validation is not accepted yet. Cannot publish page.',
            ]);
    }

    public function test_a3i_public_infrastructure_discovery_success()
    {
        $response = $this->getJson('/api/v1/public/pages/edge-gateway-cluster');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'page' => [
                    'title',
                    'slug',
                    'developer' => ['full_name', 'github_url'],
                    'sections'  => [
                        '*' => [
                            'id',
                            'position',
                            'template_id',
                            'fields' => [
                                '*' => ['template_field_id', 'value'],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'page' => [
                    'title' => 'Edge Gateway Cluster',
                    'slug'  => 'edge-gateway-cluster',
                    'developer' => [
                        'full_name'  => 'Alex Mercer',
                        'github_url' => 'https://github.com/alexmercer',
                    ],
                ],
            ]);
    }

    public function test_a3j_public_infrastructure_discovery_not_found()
    {
        $response = $this->getJson('/api/v1/public/pages/unknown-cluster-slug');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Page not found or not published',
            ]);
    }

    /** A4: Server Cluster Financing & Lease **/

    public function test_a4a_get_server_clusters_with_filters_success()
    {
        $response = $this->getJson('/api/v1/server_clusters?category=gpu_cluster&max_price=5000&sort_by=price&order=desc');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clusters' => [
                    '*' => ['id', 'name', 'category', 'price', 'available_months']
                ]
            ]);
    }

    public function test_a4b_get_server_clusters_invalid_query_parameters()
    {
        $response = $this->getJson('/api/v1/server_clusters?category=quantum_compute&order=diagonal');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid query parameters',
            ]);
    }

    public function test_a4c_apply_financing_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/applications', [
                'cluster_id' => 4,
                'months'     => 12,
                'notes'      => 'Dedicated training node for local LLM inference.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'application' => ['id', 'cluster_id', 'months', 'monthly_nominal', 'status']
            ])
            ->assertJson([
                'message' => 'Applying for server cluster financing successful',
                'application' => [
                    'cluster_id'      => 4,
                    'months'          => 12,
                    'monthly_nominal' => '400.00',
                    'status'          => 'pending',
                ]
            ]);
    }

    public function test_a4d_apply_financing_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->postJson('/api/v1/applications', [
                'cluster_id' => 4,
                'months'     => 12,
                'notes'      => 'Notes',
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized user',
            ]);
    }

    public function test_a4e_apply_financing_profile_not_accepted()
    {
        $developer = Developer::where('username', 'sarahc')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/applications', [
                'cluster_id' => 4,
                'months'     => 12,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your profile validation must be accepted by auditor before applying',
            ]);
    }

    public function test_a4f_apply_financing_invalid_fields()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/applications', [
                'cluster_id' => 9999,
                'months'     => 5,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid field',
                'errors'  => [
                    'cluster_id' => [
                        'The selected cluster id is invalid.'
                    ],
                    'months' => [
                        'The selected months tenure is not available for this server cluster.'
                    ]
                ]
            ]);
    }

    public function test_a4g_apply_financing_income_criteria_failed()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/applications', [
                'cluster_id' => 4,
                'months'     => 6,
                'notes'      => 'Monthly nominal exceeds total monthly income.',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Monthly income insufficient for this server cluster lease plan',
            ]);
    }

    public function test_a4h_apply_financing_active_lease_exists()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/applications', [
                'cluster_id' => 1,
                'months'     => 24,
                'notes'      => 'Concurrent lease attempt.',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User already holds an active lease agreement',
            ]);
    }

    public function test_a4i_get_developer_lease_applications_success()
    {
        $developer = Developer::where('username', 'alexm')->first();
        $token = $developer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'applications' => [
                    '*' => [
                        'id',
                        'cluster' => ['id', 'name', 'category', 'price'],
                        'months',
                        'monthly_nominal',
                        'status',
                        'notes',
                        'created_at'
                    ]
                ]
            ]);
    }

    public function test_a4j_get_developer_lease_applications_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/v1/applications');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized user',
            ]);
    }
}

