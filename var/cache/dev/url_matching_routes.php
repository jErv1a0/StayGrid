<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/rooms' => [[['_route' => 'api_rooms', '_controller' => 'App\\Controller\\ApiAuthController::rooms'], null, ['GET' => 0], null, false, false, null]],
        '/api/bookings' => [
            [['_route' => 'api_bookings', '_controller' => 'App\\Controller\\ApiAuthController::bookings'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_bookings_create', '_controller' => 'App\\Controller\\ApiAuthController::createBooking'], null, ['POST' => 0], null, false, false, null],
        ],
        '/_wdt/styles' => [[['_route' => '_wdt_stylesheet', '_controller' => 'web_profiler.controller.profiler::toolbarStylesheetAction'], null, null, null, false, false, null]],
        '/_profiler' => [[['_route' => '_profiler_home', '_controller' => 'web_profiler.controller.profiler::homeAction'], null, null, null, true, false, null]],
        '/_profiler/search' => [[['_route' => '_profiler_search', '_controller' => 'web_profiler.controller.profiler::searchAction'], null, null, null, false, false, null]],
        '/_profiler/search_bar' => [[['_route' => '_profiler_search_bar', '_controller' => 'web_profiler.controller.profiler::searchBarAction'], null, null, null, false, false, null]],
        '/_profiler/phpinfo' => [[['_route' => '_profiler_phpinfo', '_controller' => 'web_profiler.controller.profiler::phpinfoAction'], null, null, null, false, false, null]],
        '/_profiler/xdebug' => [[['_route' => '_profiler_xdebug', '_controller' => 'web_profiler.controller.profiler::xdebugAction'], null, null, null, false, false, null]],
        '/_profiler/open' => [[['_route' => '_profiler_open_file', '_controller' => 'web_profiler.controller.profiler::openAction'], null, null, null, false, false, null]],
        '/about' => [[['_route' => 'app_about', '_controller' => 'App\\Controller\\AboutController::index'], null, null, null, false, false, null]],
        '/admin/activity-logs' => [[['_route' => 'app_admin_activity_logs_index', '_controller' => 'App\\Controller\\Admin\\ActivityLogController::index'], null, ['GET' => 0], null, false, false, null]],
        '/admin' => [[['_route' => 'app_admin_home', '_controller' => 'App\\Controller\\Admin\\AdminHomeController::home'], null, null, null, false, false, null]],
        '/admin/profile' => [[['_route' => 'app_admin_profile', '_controller' => 'App\\Controller\\Admin\\AdminProfileController::index'], null, ['GET' => 0], null, false, false, null]],
        '/admin/bookings' => [[['_route' => 'app_admin_bookings_index', '_controller' => 'App\\Controller\\Admin\\BookingController::index'], null, ['GET' => 0], null, true, false, null]],
        '/admin/bookings/new' => [[['_route' => 'app_admin_bookings_new', '_controller' => 'App\\Controller\\Admin\\BookingController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/admin/roomlisting' => [[['_route' => 'app_admin_roomlisting_index', '_controller' => 'App\\Controller\\Admin\\RoomListingController::index'], null, ['GET' => 0], null, true, false, null]],
        '/admin/roomlisting/new' => [[['_route' => 'app_admin_roomlisting_new', '_controller' => 'App\\Controller\\Admin\\RoomListingController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/admin/login' => [[['_route' => 'app_admin_login', '_controller' => 'App\\Controller\\Admin\\SecurityAdminController::login'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/admin/logout' => [[['_route' => 'app_admin_logout', '_controller' => 'App\\Controller\\Admin\\SecurityAdminController::logout'], null, null, null, false, false, null]],
        '/admin/user-activity' => [[['_route' => 'app_admin_user_activity_index', '_controller' => 'App\\Controller\\Admin\\UserActivityController::index'], null, ['GET' => 0], null, true, false, null]],
        '/admin/user-activity/debug/create' => [[['_route' => 'app_admin_user_activity_debug', '_controller' => 'App\\Controller\\Admin\\UserActivityController::debugCreate'], null, ['GET' => 0], null, false, false, null]],
        '/admin/users' => [[['_route' => 'app_admin_users_index', '_controller' => 'App\\Controller\\Admin\\UserController::index'], null, null, null, true, false, null]],
        '/api/register' => [[['_route' => 'api_register', '_controller' => 'App\\Controller\\ApiAuthController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/verify-email' => [[['_route' => 'api_verify_email', '_controller' => 'App\\Controller\\ApiAuthController::verifyEmail'], null, ['GET' => 0], null, false, false, null]],
        '/api/login' => [
            [['_route' => 'api_login_info', '_controller' => 'App\\Controller\\ApiAuthController::loginInfo'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_login', '_controller' => 'App\\Controller\\ApiAuthController::login'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/me' => [[['_route' => 'api_me', '_controller' => 'App\\Controller\\ApiAuthController::me'], null, ['GET' => 0], null, false, false, null]],
        '/api/logout' => [[['_route' => 'api_logout', '_controller' => 'App\\Controller\\ApiAuthController::logout'], null, ['POST' => 0], null, false, false, null]],
        '/book' => [[['_route' => 'app_booking', '_controller' => 'App\\Controller\\BookController::book'], null, null, null, false, false, null]],
        '/book/now' => [[['_route' => 'app_book_now', '_controller' => 'App\\Controller\\BookNowController::index'], null, null, null, false, false, null]],
        '/booking/login' => [[['_route' => 'app_booking_login', '_controller' => 'App\\Controller\\BookingLoginController::index'], null, null, null, false, false, null]],
        '/bookings' => [[['_route' => 'app_bookings', '_controller' => 'App\\Controller\\BookingsController::index'], null, null, null, false, false, null]],
        '/contact' => [[['_route' => 'app_contact', '_controller' => 'App\\Controller\\ContactController::index'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/whoami' => [[['_route' => 'app_whoami', '_controller' => 'App\\Controller\\Debug\\WhoAmIController::whoami'], null, ['GET' => 0], null, false, false, null]],
        '/test-email' => [[['_route' => 'test_email', '_controller' => 'App\\Controller\\EmailTestController::testEmail'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_home', '_controller' => 'App\\Controller\\HomeController::index'], null, null, null, false, false, null]],
        '/index' => [[['_route' => 'app_index', '_controller' => 'App\\Controller\\IndexController::index'], null, null, null, false, false, null]],
        '/landing' => [[['_route' => 'app_landing', '_controller' => 'App\\Controller\\LandingController::index'], null, null, null, false, false, null]],
        '/new' => [[['_route' => 'app_new', '_controller' => 'App\\Controller\\NewController::index'], null, null, null, false, false, null]],
        '/register' => [[['_route' => 'app_register', '_controller' => 'App\\Controller\\RegistrationController::register'], null, null, null, false, false, null]],
        '/verify/email' => [[['_route' => 'app_verify_email', '_controller' => 'App\\Controller\\RegistrationController::verifyUserEmail'], null, null, null, false, false, null]],
        '/connect/google' => [[['_route' => 'connect_google_start', '_controller' => 'App\\Controller\\RegistrationController::connectGoogle'], null, null, null, false, false, null]],
        '/connect/google/check' => [[['_route' => 'connect_google_check', '_controller' => 'App\\Controller\\RegistrationController::connectGoogleCheck'], null, null, null, false, false, null]],
        '/listings' => [[['_route' => 'app_roomlisting_index', '_controller' => 'App\\Controller\\RoomListingController::index'], null, ['GET' => 0], null, true, false, null]],
        '/rooms' => [[['_route' => 'app_rooms', '_controller' => 'App\\Controller\\RoomsController::rooms'], null, null, null, false, false, null]],
        '/rooms/studio-deluxe' => [[['_route' => 'app_room_studio_deluxe', '_controller' => 'App\\Controller\\RoomsController::studioDeluxe'], null, null, null, false, false, null]],
        '/rooms/executive-suite' => [[['_route' => 'app_room_executive_suite', '_controller' => 'App\\Controller\\RoomsController::executiveSuite'], null, null, null, false, false, null]],
        '/rooms/family-apartment' => [[['_route' => 'app_room_family_apartment', '_controller' => 'App\\Controller\\RoomsController::familyApartment'], null, null, null, false, false, null]],
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\SecurityController::login'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/logout' => [[['_route' => 'app_logout', '_controller' => 'App\\Controller\\SecurityController::logout'], null, null, null, false, false, null]],
        '/staff/roomlisting' => [[['_route' => 'app_staff_roomlisting_index', '_controller' => 'App\\Controller\\Staff\\RoomListingController::index'], null, ['GET' => 0], null, true, false, null]],
        '/staff/roomlisting/new' => [[['_route' => 'app_staff_roomlisting_new', '_controller' => 'App\\Controller\\Staff\\RoomListingController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/staff' => [
            [['_route' => 'app_staff_dashboard', '_controller' => 'App\\Controller\\Staff\\StaffHomeController::home'], null, null, null, false, false, null],
            [['_route' => 'app_staff_dashboard_slash', '_controller' => 'App\\Controller\\Staff\\StaffHomeController::slash'], null, null, null, true, false, null],
        ],
        '/staff/dashboard' => [[['_route' => 'app_staff_dashboard_alias', '_controller' => 'App\\Controller\\Staff\\StaffHomeController::home'], null, null, null, false, false, null]],
        '/staff/profile' => [[['_route' => 'app_staff_profile', '_controller' => 'App\\Controller\\Staff\\StaffProfileController::index'], null, ['GET' => 0], null, false, false, null]],
        '/staff/profile/edit' => [[['_route' => 'app_staff_profile_edit', '_controller' => 'App\\Controller\\Staff\\StaffProfileController::edit'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/staff/users' => [[['_route' => 'app_staff_users_index', '_controller' => 'App\\Controller\\Staff\\StaffUserController::index'], null, ['GET' => 0], null, true, false, null]],
        '/user/booking' => [[['_route' => 'app_client_booking_index', '_controller' => 'App\\Controller\\User\\BookingController::index'], null, ['GET' => 0], null, true, false, null]],
        '/user/rooms' => [[['_route' => 'app_user_rooms_rooms', '_controller' => 'App\\Controller\\User\\RoomsController::rooms'], null, null, null, false, false, null]],
        '/user' => [[['_route' => 'app_user_dashboard', '_controller' => 'App\\Controller\\User\\UserHomeController::dashboard'], null, null, null, false, false, null]],
        '/user/profile' => [[['_route' => 'app_user_profile', '_controller' => 'App\\Controller\\User\\UserProfileController::index'], null, ['GET' => 0], null, false, false, null]],
        '/user/profile/edit' => [[['_route' => 'app_user_profile_edit', '_controller' => 'App\\Controller\\User\\UserProfileController::edit'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/admin/profile/edit' => [[['_route' => 'app_admin_profile_edit', '_controller' => 'App\\Controller\\Admin\\AdminProfileController::edit'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/a(?'
                    .'|pi(?'
                        .'|/(?'
                            .'|docs(?:\\.([^/]++))?(*:40)'
                            .'|\\.well\\-known/genid/([^/]++)(*:75)'
                            .'|validation_errors/([^/]++)(*:108)'
                        .')'
                        .'|(?:/(index)(?:\\.([^/]++))?)?(*:145)'
                        .'|/(?'
                            .'|contexts/([^.]+)(?:\\.(jsonld))?(*:188)'
                            .'|errors/(\\d+)(?:\\.([^/]++))?(*:223)'
                            .'|validation_errors/([^/]++)(?'
                                .'|(*:260)'
                            .')'
                            .'|bookings(?'
                                .'|/([^/\\.]++)(?:\\.([^/]++))?(*:306)'
                                .'|(?:\\.([^/]++))?(?'
                                    .'|(*:332)'
                                .')'
                                .'|/([^/\\.]++)(?:\\.([^/]++))?(?'
                                    .'|(*:370)'
                                .')'
                            .')'
                            .'|log_in_users(?'
                                .'|/([^/\\.]++)(?:\\.([^/]++))?(*:421)'
                                .'|(?:\\.([^/]++))?(*:444)'
                            .')'
                            .'|room_listings(?'
                                .'|/([^/\\.]++)(?:\\.([^/]++))?(*:495)'
                                .'|(?:\\.([^/]++))?(?'
                                    .'|(*:521)'
                                .')'
                                .'|/([^/\\.]++)(?:\\.([^/]++))?(?'
                                    .'|(*:559)'
                                .')'
                            .')'
                        .')'
                    .')'
                    .'|dmin/(?'
                        .'|bookings/([^/]++)(?'
                            .'|/edit(*:604)'
                            .'|(*:612)'
                        .')'
                        .'|roomlisting/([^/]++)(?'
                            .'|(*:644)'
                            .'|/edit(*:657)'
                            .'|(*:665)'
                        .')'
                        .'|users/([^/]++)/(?'
                            .'|verify(*:698)'
                            .'|unverify(*:714)'
                        .')'
                    .')'
                .')'
                .'|/_(?'
                    .'|error/(\\d+)(?:\\.([^/]++))?(*:756)'
                    .'|wdt/([^/]++)(*:776)'
                    .'|profiler/(?'
                        .'|font/([^/\\.]++)\\.woff2(*:818)'
                        .'|([^/]++)(?'
                            .'|/(?'
                                .'|search/results(*:855)'
                                .'|router(*:869)'
                                .'|exception(?'
                                    .'|(*:889)'
                                    .'|\\.css(*:902)'
                                .')'
                            .')'
                            .'|(*:912)'
                        .')'
                    .')'
                .')'
                .'|/listings/([^/]++)(*:941)'
                .'|/staff/(?'
                    .'|bookings/(?'
                        .'|([^/]++)(?'
                            .'|(*:982)'
                            .'|/edit(*:995)'
                        .')'
                        .'|new(?:/([^/]++))?(*:1021)'
                        .'|([^/]++)/delete(*:1045)'
                    .')'
                    .'|roomlisting/(?'
                        .'|([^/]++)(?'
                            .'|/edit(*:1086)'
                            .'|(*:1095)'
                        .')'
                        .'|bookings(*:1113)'
                    .')'
                .')'
                .'|/user/(?'
                    .'|booking/(?'
                        .'|new/([^/]++)(*:1156)'
                        .'|([^/]++)(*:1173)'
                    .')'
                    .'|rooms/([^/]++)(*:1197)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        40 => [[['_route' => 'api_doc', '_controller' => 'api_platform.action.documentation', '_format' => null, '_api_respond' => true], ['_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        75 => [[['_route' => 'api_genid', '_controller' => 'api_platform.action.not_exposed', '_api_respond' => true], ['id'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        108 => [[['_route' => 'api_validation_errors', '_controller' => 'api_platform.action.not_exposed'], ['id'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        145 => [[['_route' => 'api_entrypoint', '_controller' => 'api_platform.action.entrypoint', '_format' => null, '_api_respond' => true, 'index' => 'index'], ['index', '_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        188 => [[['_route' => 'api_jsonld_context', '_controller' => 'api_platform.jsonld.action.context', '_format' => 'jsonld', '_api_respond' => true], ['shortName', '_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        223 => [[['_route' => '_api_errors', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => null, '_api_resource_class' => 'ApiPlatform\\State\\ApiResource\\Error', '_api_operation_name' => '_api_errors', '_format' => null], ['status', '_format'], ['GET' => 0], null, false, true, null]],
        260 => [
            [['_route' => '_api_validation_errors_problem', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => null, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_problem', '_format' => null], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_validation_errors_hydra', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => null, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_hydra', '_format' => null], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_validation_errors_jsonapi', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => null, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_jsonapi', '_format' => null], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_validation_errors_xml', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => null, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_xml', '_format' => null], ['id'], ['GET' => 0], null, false, true, null],
        ],
        306 => [[['_route' => '_api_/bookings/{id}{._format}_get', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\Booking', '_api_operation_name' => '_api_/bookings/{id}{._format}_get', '_format' => null], ['id', '_format'], ['GET' => 0], null, false, true, null]],
        332 => [
            [['_route' => '_api_/bookings{._format}_get_collection', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\Booking', '_api_operation_name' => '_api_/bookings{._format}_get_collection', '_format' => null], ['_format'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_/bookings{._format}_post', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\Booking', '_api_operation_name' => '_api_/bookings{._format}_post', '_format' => null], ['_format'], ['POST' => 0], null, false, true, null],
        ],
        370 => [
            [['_route' => '_api_/bookings/{id}{._format}_put', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\Booking', '_api_operation_name' => '_api_/bookings/{id}{._format}_put', '_format' => null], ['id', '_format'], ['PUT' => 0], null, false, true, null],
            [['_route' => '_api_/bookings/{id}{._format}_delete', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\Booking', '_api_operation_name' => '_api_/bookings/{id}{._format}_delete', '_format' => null], ['id', '_format'], ['DELETE' => 0], null, false, true, null],
        ],
        421 => [[['_route' => '_api_/log_in_users/{id}{._format}_get', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\LogInUsers', '_api_operation_name' => '_api_/log_in_users/{id}{._format}_get', '_format' => null], ['id', '_format'], ['GET' => 0], null, false, true, null]],
        444 => [[['_route' => '_api_/log_in_users{._format}_get_collection', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\LogInUsers', '_api_operation_name' => '_api_/log_in_users{._format}_get_collection', '_format' => null], ['_format'], ['GET' => 0], null, false, true, null]],
        495 => [[['_route' => '_api_/room_listings/{id}{._format}_get', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\RoomListing', '_api_operation_name' => '_api_/room_listings/{id}{._format}_get', '_format' => null], ['id', '_format'], ['GET' => 0], null, false, true, null]],
        521 => [
            [['_route' => '_api_/room_listings{._format}_get_collection', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\RoomListing', '_api_operation_name' => '_api_/room_listings{._format}_get_collection', '_format' => null], ['_format'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_/room_listings{._format}_post', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\RoomListing', '_api_operation_name' => '_api_/room_listings{._format}_post', '_format' => null], ['_format'], ['POST' => 0], null, false, true, null],
        ],
        559 => [
            [['_route' => '_api_/room_listings/{id}{._format}_put', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\RoomListing', '_api_operation_name' => '_api_/room_listings/{id}{._format}_put', '_format' => null], ['id', '_format'], ['PUT' => 0], null, false, true, null],
            [['_route' => '_api_/room_listings/{id}{._format}_delete', '_controller' => 'api_platform.symfony.main_controller', '_stateless' => false, '_api_resource_class' => 'App\\Entity\\RoomListing', '_api_operation_name' => '_api_/room_listings/{id}{._format}_delete', '_format' => null], ['id', '_format'], ['DELETE' => 0], null, false, true, null],
        ],
        604 => [[['_route' => 'app_admin_bookings_edit', '_controller' => 'App\\Controller\\Admin\\BookingController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        612 => [
            [['_route' => 'app_admin_bookings_delete', '_controller' => 'App\\Controller\\Admin\\BookingController::delete'], ['id'], ['POST' => 0], null, false, true, null],
            [['_route' => 'app_admin_bookings_show', '_controller' => 'App\\Controller\\Admin\\BookingController::show'], ['id'], ['GET' => 0], null, false, true, null],
        ],
        644 => [[['_route' => 'app_admin_roomlisting_show', '_controller' => 'App\\Controller\\Admin\\RoomListingController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        657 => [[['_route' => 'app_admin_roomlisting_edit', '_controller' => 'App\\Controller\\Admin\\RoomListingController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        665 => [[['_route' => 'app_admin_roomlisting_delete', '_controller' => 'App\\Controller\\Admin\\RoomListingController::delete'], ['id'], ['POST' => 0], null, false, true, null]],
        698 => [[['_route' => 'app_admin_users_verify', '_controller' => 'App\\Controller\\Admin\\UserController::verify'], ['id'], ['POST' => 0], null, false, false, null]],
        714 => [[['_route' => 'app_admin_users_unverify', '_controller' => 'App\\Controller\\Admin\\UserController::unverify'], ['id'], ['POST' => 0], null, false, false, null]],
        756 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        776 => [[['_route' => '_wdt', '_controller' => 'web_profiler.controller.profiler::toolbarAction'], ['token'], null, null, false, true, null]],
        818 => [[['_route' => '_profiler_font', '_controller' => 'web_profiler.controller.profiler::fontAction'], ['fontName'], null, null, false, false, null]],
        855 => [[['_route' => '_profiler_search_results', '_controller' => 'web_profiler.controller.profiler::searchResultsAction'], ['token'], null, null, false, false, null]],
        869 => [[['_route' => '_profiler_router', '_controller' => 'web_profiler.controller.router::panelAction'], ['token'], null, null, false, false, null]],
        889 => [[['_route' => '_profiler_exception', '_controller' => 'web_profiler.controller.exception_panel::body'], ['token'], null, null, false, false, null]],
        902 => [[['_route' => '_profiler_exception_css', '_controller' => 'web_profiler.controller.exception_panel::stylesheet'], ['token'], null, null, false, false, null]],
        912 => [[['_route' => '_profiler', '_controller' => 'web_profiler.controller.profiler::panelAction'], ['token'], null, null, false, true, null]],
        941 => [[['_route' => 'app_roomlisting_show', '_controller' => 'App\\Controller\\RoomListingController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        982 => [[['_route' => 'app_staff_booking_show', '_controller' => 'App\\Controller\\Staff\\BookingController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        995 => [[['_route' => 'app_staff_booking_edit', '_controller' => 'App\\Controller\\Staff\\BookingController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        1021 => [[['_route' => 'app_staff_booking_new', 'roomId' => null, '_controller' => 'App\\Controller\\Staff\\BookingController::new'], ['roomId'], ['GET' => 0, 'POST' => 1], null, false, true, null]],
        1045 => [[['_route' => 'app_staff_booking_delete', '_controller' => 'App\\Controller\\Staff\\BookingController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        1086 => [[['_route' => 'app_staff_roomlisting_edit', '_controller' => 'App\\Controller\\Staff\\RoomListingController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        1095 => [[['_route' => 'app_staff_roomlisting_delete', '_controller' => 'App\\Controller\\Staff\\RoomListingController::delete'], ['id'], ['POST' => 0], null, false, true, null]],
        1113 => [[['_route' => 'app_staff_roomlisting_bookings', '_controller' => 'App\\Controller\\Staff\\RoomListingController::bookings'], [], ['GET' => 0], null, false, false, null]],
        1156 => [[['_route' => 'app_booking_new', '_controller' => 'App\\Controller\\User\\BookingController::new'], ['roomId'], ['GET' => 0, 'POST' => 1], null, false, true, null]],
        1173 => [[['_route' => 'app_client_booking_show', '_controller' => 'App\\Controller\\User\\BookingController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        1197 => [
            [['_route' => 'app_user_rooms_show', '_controller' => 'App\\Controller\\User\\RoomsController::show'], ['id'], ['GET' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
