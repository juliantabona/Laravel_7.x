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
            statusTypes: ['200', '201', '203', '204', '205', '300', '400', '401', '403', '404', '405', '500']
        }
    },
    methods: {

        isGoodStatus( statusType ){

            return ['1', '2', '3'].includes(statusType.substring(0, 1)) ? true : false;

        }
        
    }
}

export default mixin;