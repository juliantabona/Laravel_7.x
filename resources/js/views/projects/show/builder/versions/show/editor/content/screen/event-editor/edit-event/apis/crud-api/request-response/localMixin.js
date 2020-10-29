// Custom defined mixin object
var mixin = {
    data() {
        return {
            automaticSuccessOptions: [
                { 
                    name: 'Display Default Success Message',
                    value: 'use_default_success_msg'
                },
                { 
                    name: 'Do Nothing',
                    value: 'do_nothing'
                }
            ],
            automaticErrorOptions: [
                { 
                    name: 'Display Default Error Message',
                    value: 'use_default_error_msg'
                },
                { 
                    name: 'Do Nothing',
                    value: 'do_nothing'
                }
            ],
            manualOptions: [
                { 
                    name: 'Display Custom Message',
                    value: 'use_custom_msg'
                },
                { 
                    name: 'Do Nothing',
                    value: 'do_nothing'
                }
            ],
            apiResponseTypes: [
                { 
                    name: 'Automatic Responses',
                    value: 'automatic'
                },
                { 
                    name: 'Manual Responses',
                    value: 'manual'
                }
            ],
            statusTypes: [
                //  1xx
                {'code': '100', 'desc': 'Continue'},
                {'code': '101', 'desc': 'Switching Protocols'},
                {'code': '102', 'desc': 'Processing (WebDAV)'},
                {'code': '1xx', 'desc': 'Catch Status: 100 <= x < 200'},

                //  2xx
                {'code': '200', 'desc': 'OK'},
                {'code': '201', 'desc': 'Created'},
                {'code': '202', 'desc': 'Accepted'},
                {'code': '203', 'desc': 'Non-Authoritative Information'},
                {'code': '204', 'desc': 'No Content'},
                {'code': '205', 'desc': 'Reset Content'},
                {'code': '206', 'desc': 'Partial Content'},
                {'code': '207', 'desc': 'Multi-Status (WebDAV)'},
                {'code': '208', 'desc': 'Already Reported (WebDAV)'},
                {'code': '226', 'desc': 'IM Used'},
                {'code': '2xx', 'desc': 'Catch Status: 200 <= x < 300'},

                //  3xx
                {'code': '300', 'desc': 'Multiple Choices'},
                {'code': '301', 'desc': 'Moved Permanently'},
                {'code': '302', 'desc': 'Found'},
                {'code': '303', 'desc': 'See Other'},
                {'code': '304', 'desc': 'Not Modified'},
                {'code': '305', 'desc': 'Use Proxy'},
                {'code': '306', 'desc': '(Unused)'},
                {'code': '307', 'desc': 'Temporary Redirect'},
                {'code': '308', 'desc': 'Permanent Redirect (experimental)'},
                {'code': '3xx', 'desc': 'Catch Status: 200 <= x < 300'},

                //  4xx
                {'code': '400', 'desc': 'Bad Request'},
                {'code': '401', 'desc': 'Unauthorized'},
                {'code': '402', 'desc': 'Payment Required'},
                {'code': '403', 'desc': 'Forbidden'},
                {'code': '404', 'desc': 'Not Found'},
                {'code': '405', 'desc': 'Method Not Allowed'},
                {'code': '406', 'desc': 'Not Acceptable'},
                {'code': '407', 'desc': 'Proxy Authentication Required'},
                {'code': '408', 'desc': 'Request Timeout'},
                {'code': '409', 'desc': 'Conflict'},
                {'code': '410', 'desc': 'Gone'},
                {'code': '411', 'desc': 'Length Required'},
                {'code': '412', 'desc': 'Precondition Failed'},
                {'code': '413', 'desc': 'Request Entity Too Large'},
                {'code': '414', 'desc': 'Request-URI Too Long'},
                {'code': '415', 'desc': 'Unsupported Media Type'},
                {'code': '416', 'desc': 'Requested Range Not Satisfiable'},
                {'code': '417', 'desc': 'Expectation Failed'},
                {'code': '418', 'desc': 'I\'m a teapot (RFC 2324)'},
                {'code': '420', 'desc': 'Enhance Your Calm (Twitter)'},
                {'code': '422', 'desc': 'Unprocessable Entity (WebDAV)'},
                {'code': '423', 'desc': 'Locked (WebDAV)'},
                {'code': '424', 'desc': 'Failed Dependency (WebDAV)'},
                {'code': '425', 'desc': 'Reserved for WebDAV'},
                {'code': '426', 'desc': 'Upgrade Required'},
                {'code': '428', 'desc': 'Precondition Required'},
                {'code': '429', 'desc': 'Too Many Requests'},
                {'code': '431', 'desc': 'Request Header Fields Too Large'},
                {'code': '449', 'desc': 'Retry With (Microsoft)'},
                {'code': '450', 'desc': 'Blocked by Windows Parental Controls (Microsoft)'},
                {'code': '451', 'desc': 'Unavailable For Legal Reasons'},
                {'code': '499', 'desc': 'Client Closed Request (Nginx)'},
                {'code': '4xx', 'desc': 'Catch Status: 400 <= x < 500'},

                //  5xx
                {'code': '500', 'desc': 'Internal Server Error'},
                {'code': '501', 'desc': 'Not Implemented'},
                {'code': '502', 'desc': 'Bad Gateway'},
                {'code': '503', 'desc': 'Service Unavailable'},
                {'code': '504', 'desc': 'Gateway Timeout'},
                {'code': '505', 'desc': 'HTTP Version Not Supported'},
                {'code': '506', 'desc': 'Variant Also Negotiates (Experimental)'},
                {'code': '507', 'desc': 'Insufficient Storage (WebDAV)'},
                {'code': '508', 'desc': 'Loop Detected (WebDAV)'},
                {'code': '509', 'desc': 'Bandwidth Limit Exceeded (Apache)'},
                {'code': '510', 'desc': 'Not Extended'},
                {'code': '511', 'desc': 'Network Authentication Required'},
                {'code': '598', 'desc': 'Network read timeout error'},
                {'code': '599', 'desc': 'Network connect timeout error'},
                {'code': '5xx', 'desc': 'Catch Status: x >= 500'}
            ]
        }
    },
    methods: {

        isGoodStatus( statusType ){

            return ['1', '2', '3'].includes(statusType.substring(0, 1)) ? true : false;

        }
        
    }
}

export default mixin;