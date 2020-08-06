<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Billing Event Instructions -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Select the <span class="font-italic text-success font-weight-bold">Payment Methods</span>
                that should be available for automated payment processing.
            </Alert>

        </Col>

    </Row>

</template>

<script>

    //  Get the loader
    import Loader from './../../../../../../../../../../../../../../components/_common/loaders/default.vue';

    export default {
        components: { Loader },
        props: {
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        data(){
            return{
                payment_methods: null,
                isLoading: false
            }
        },
        computed: {
            paymentMethodsUrl(){
                return this.project['_links']['sce:payment_methods'].href);
            }
        },
        methods: {
            fetchPaymentMethods() {

                //  If we have the version url
                if( this.paymentMethodsUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.paymentMethodsUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the payment methods
                            self.payment_methods = data || [];

                            //  Stop loader
                            self.isLoading = false;

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            }
        }
    };
  
</script>