<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Keycloak Server Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the connection settings for your Keycloak
    | server. The server URL should point to your Keycloak instance root
    | (e.g., https://auth.example.com or http://localhost:8080).
    |
    */

    'server_url' => env('KEYCLOAK_SERVER_URL', 'https://keycloak.anephemeralapp.xyz/'),

    /*
    |--------------------------------------------------------------------------
    | Default Realm
    |--------------------------------------------------------------------------
    |
    | The default realm to use for admin operations. This can be overridden
    | at runtime using the withRealm() method on the client instance.
    | Common values are 'master' for admin operations or your app realm.
    |
    */

    'realm' => env('KEYCLOAK_REALM', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | These settings configure the underlying HTTP client behavior for
    | requests to the Keycloak Admin API.
    |
    */

    'timeout' => (int) env('KEYCLOAK_TIMEOUT', 30),

    'connect_timeout' => (int) env('KEYCLOAK_CONNECT_TIMEOUT', 10),

    'read_timeout' => (int) env('KEYCLOAK_READ_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed requests. This helps
    | handle temporary network issues and server unavailability.
    |
    */

    'retry' => [
        'times' => (int) env('KEYCLOAK_RETRY_TIMES', 3),
        'sleep' => (int) env('KEYCLOAK_RETRY_SLEEP', 100), // milliseconds
        'when' => [
            // HTTP status codes that should trigger a retry
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
        ],
        'throw' => env('KEYCLOAK_RETRY_THROW', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure response caching to improve performance. Cached responses
    | are automatically invalidated when related data is modified.
    |
    */

    'cache' => [
        'enabled' => env('KEYCLOAK_CACHE_ENABLED', false),
        'ttl' => (int) env('KEYCLOAK_CACHE_TTL', 300), // seconds (5 minutes)
        'prefix' => env('KEYCLOAK_CACHE_PREFIX', 'keycloak_admin'),
        'store' => env('KEYCLOAK_CACHE_STORE'), // Use default cache store if null
        'tags' => [
            'roles' => 'keycloak:roles',
            'groups' => 'keycloak:groups',
            'users' => 'keycloak:users',
            'clients' => 'keycloak:clients',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for the Keycloak Admin SDK. This helps with
    | debugging and monitoring API interactions.
    |
    */

    'logging' => [
        'enabled' => env('KEYCLOAK_LOGGING_ENABLED', true),
        'channel' => env('KEYCLOAK_LOG_CHANNEL', 'keycloak'),
        'level' => env('KEYCLOAK_LOG_LEVEL', 'info'),
        'requests' => env('KEYCLOAK_LOG_REQUESTS', true),
        'responses' => env('KEYCLOAK_LOG_RESPONSES', false), // Be careful with sensitive data
        'slow_queries' => [
            'enabled' => env('KEYCLOAK_LOG_SLOW_QUERIES', true),
            'threshold' => (int) env('KEYCLOAK_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for the Keycloak Admin SDK.
    |
    */

    'security' => [
        // Verify SSL certificates (set to false only in development)
        'verify_ssl' => env('KEYCLOAK_VERIFY_SSL', true),

        // SSL certificate path (optional, for self-signed certificates)
        'ssl_cert_path' => env('KEYCLOAK_SSL_CERT_PATH'),

        // Token validation settings
        'token_validation' => [
            'verify_signature' => env('KEYCLOAK_VERIFY_TOKEN_SIGNATURE', true),
            'check_expiration' => env('KEYCLOAK_CHECK_TOKEN_EXPIRATION', true),
            'leeway' => (int) env('KEYCLOAK_TOKEN_LEEWAY', 60), // seconds
        ],

        // Rate limiting protection
        'rate_limiting' => [
            'enabled' => env('KEYCLOAK_RATE_LIMITING_ENABLED', true),
            'max_requests_per_minute' => (int) env('KEYCLOAK_MAX_REQUESTS_PER_MINUTE', 100),
            'backoff_strategy' => env('KEYCLOAK_BACKOFF_STRATEGY', 'exponential'), // linear, exponential
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | Default headers to include with every request to the Keycloak Admin API.
    | You can add custom headers here or override them at runtime.
    |
    */

    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'User-Agent' => 'Laravel-Keycloak-Admin-SDK/' . (env('KEYCLOAK_SDK_VERSION', '1.0')),
        'X-Requested-With' => 'XMLHttpRequest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Logic Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for business logic and validation rules.
    |
    */

    'business_rules' => [
        // Protected role names that cannot be deleted
        'protected_roles' => [
            'admin',
            'create-realm',
            'default-roles-realm',
            'offline_access',
            'uma_authorization',
        ],

        // Reserved role names that cannot be created
        'reserved_role_names' => [
            'admin',
            'default-roles-realm',
            'offline_access',
            'uma_authorization',
        ],

        // Role name validation
        'role_name' => [
            'max_length' => 255,
            'min_length' => 1,
            'pattern' => '/^[a-zA-Z0-9_-]+$/', // Only alphanumeric, underscore, hyphen
        ],

        // Role description validation
        'role_description' => [
            'max_length' => 500,
        ],

        // Group name validation
        'group_name' => [
            'max_length' => 255,
            'min_length' => 1,
            'pattern' => '/^[a-zA-Z0-9_\-\s]+$/', // Allow spaces in group names
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the SDK.
    |
    */

    'features' => [
        'role_hierarchy' => env('KEYCLOAK_FEATURE_ROLE_HIERARCHY', true),
        'bulk_operations' => env('KEYCLOAK_FEATURE_BULK_OPERATIONS', true),
        'audit_logging' => env('KEYCLOAK_FEATURE_AUDIT_LOGGING', true),
        'metrics_collection' => env('KEYCLOAK_FEATURE_METRICS', false),
        'auto_retry' => env('KEYCLOAK_FEATURE_AUTO_RETRY', true),
        'request_id_tracking' => env('KEYCLOAK_FEATURE_REQUEST_ID_TRACKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance for different use cases.
    |
    */

    'performance' => [
        // Use brief representation by default (faster, less data)
        'default_brief_representation' => env('KEYCLOAK_DEFAULT_BRIEF_REPRESENTATION', true),

        // Connection pooling
        'connection_pool' => [
            'enabled' => env('KEYCLOAK_CONNECTION_POOL_ENABLED', false),
            'max_connections' => (int) env('KEYCLOAK_MAX_CONNECTIONS', 10),
            'idle_timeout' => (int) env('KEYCLOAK_IDLE_TIMEOUT', 60), // seconds
        ],

        // Response compression
        'compression' => [
            'enabled' => env('KEYCLOAK_COMPRESSION_ENABLED', true),
            'types' => ['gzip', 'deflate'],
        ],

        // Batch operation limits
        'batch_limits' => [
            'max_operations' => (int) env('KEYCLOAK_MAX_BATCH_OPERATIONS', 100),
            'chunk_size' => (int) env('KEYCLOAK_BATCH_CHUNK_SIZE', 25),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specifically for development and testing environments.
    |
    */

    'development' => [
        // Enable debug mode for detailed logging
        'debug' => env('KEYCLOAK_DEBUG', env('APP_DEBUG', false)),

        // Fake responses for testing (when Keycloak is not available)
        'fake_responses' => env('KEYCLOAK_FAKE_RESPONSES', false),

        // Mock data directory
        'mock_data_path' => env('KEYCLOAK_MOCK_DATA_PATH', resource_path('mocks/keycloak')),

        // Development shortcuts
        'shortcuts' => [
            'skip_validation' => env('KEYCLOAK_SKIP_VALIDATION', false),
            'auto_create_test_data' => env('KEYCLOAK_AUTO_CREATE_TEST_DATA', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring SDK performance and usage.
    |
    */

    'monitoring' => [
        'enabled' => env('KEYCLOAK_MONITORING_ENABLED', false),

        'metrics' => [
            'requests_total' => true,
            'request_duration' => true,
            'cache_hit_rate' => true,
            'error_rate' => true,
        ],

        'alerts' => [
            'enabled' => env('KEYCLOAK_ALERTS_ENABLED', false),
            'error_threshold' => (float) env('KEYCLOAK_ERROR_THRESHOLD', 0.1), // 10%
            'slow_request_threshold' => (int) env('KEYCLOAK_SLOW_REQUEST_THRESHOLD', 5000), // ms
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Overrides
    |--------------------------------------------------------------------------
    |
    | Override specific settings based on the application environment.
    |
    */

    'environment_overrides' => [
        'local' => [
            'security.verify_ssl' => false,
            'logging.level' => 'debug',
            'cache.enabled' => false,
            'development.debug' => true,
        ],

        'testing' => [
            'cache.enabled' => false,
            'logging.enabled' => false,
            'development.fake_responses' => true,
            'security.verify_ssl' => false,
        ],

        'production' => [
            'security.verify_ssl' => true,
            'logging.level' => 'warning',
            'development.debug' => false,
            'monitoring.enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Configuration Profiles
    |--------------------------------------------------------------------------
    |
    | Pre-defined configuration profiles for different use cases.
    |
    */

    'profiles' => [
        'high_performance' => [
            'cache.enabled' => true,
            'cache.ttl' => 600,
            'performance.default_brief_representation' => true,
            'performance.compression.enabled' => true,
            'retry.times' => 1,
        ],

        'high_reliability' => [
            'retry.times' => 5,
            'retry.sleep' => 500,
            'security.rate_limiting.enabled' => true,
            'logging.enabled' => true,
            'monitoring.enabled' => true,
        ],

        'development' => [
            'cache.enabled' => false,
            'logging.level' => 'debug',
            'development.debug' => true,
            'security.verify_ssl' => false,
        ],
    ],

];
