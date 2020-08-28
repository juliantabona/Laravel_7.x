<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Billing Event Instructions -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Select the <span class="font-italic text-success font-weight-bold">Payment Methods</span>
                that should be available for automated payment processing.
            </Alert>
            
            <!-- Loader -->
            <Loader v-if="isLoading" class="text-left mt-2">Loading Payment Methods</Loader>

            <template v-else>
                
                <div v-if="paymentMethodsExist">
                    
                    <div v-if="event.event_data.payment_methods.length">

                        <!-- Airtime Billing -->
                        <Divider orientation="left" class="font-weight-bold">Airtime Billing</Divider>

                        <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                            
                            <div v-for="(payment_method, index) in event.event_data.payment_methods" :key="index">

                                <template v-if="payment_method.group == 'airtime'">

                                    <!-- Show active state checkbox (Marks if this is active / inactive) -->
                                    <activeStateSelector 
                                        class="mb-2"
                                        :title="payment_method.name"
                                        v-model="payment_method.active"
                                        :titleStyle="{ width: '250px' }"
                                        :disabled="payment_method.disabled"
                                        :disabledMessage="payment_method.disabled_message">
                                    </activeStateSelector>
                                    
                                </template>

                            </div>

                        </div>

                        <!-- Mobile Money -->
                        <Divider orientation="left" class="font-weight-bold mt-4">Mobile Money</Divider>

                        <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                            
                            <div v-for="(payment_method, index) in event.event_data.payment_methods" :key="index">

                                <template v-if="payment_method.group == 'mobile money'">

                                    <!-- Show active state checkbox (Marks if this is active / inactive) -->
                                    <activeStateSelector 
                                        class="mb-2"
                                        :title="payment_method.name"
                                        v-model="payment_method.active"
                                        :titleStyle="{ width: '250px' }"
                                        :disabled="payment_method.disabled"
                                        :disabledMessage="payment_method.disabled_message">
                                    </activeStateSelector>
                                    
                                </template>

                            </div>

                        </div>

                        <!-- Credit/Debit Card -->
                        <Divider orientation="left" class="font-weight-bold mt-4">Credit/Debit Card</Divider>

                        <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                        
                            <div v-for="(payment_method, index) in event.event_data.payment_methods" :key="index">

                                <template v-if="payment_method.group == 'card'">

                                    <!-- Show active state checkbox (Marks if this is active / inactive) -->
                                    <activeStateSelector 
                                        class="mb-2"
                                        :title="payment_method.name"
                                        v-model="payment_method.active"
                                        :titleStyle="{ width: '250px' }"
                                        :disabled="payment_method.disabled"
                                        :disabledMessage="payment_method.disabled_message">
                                    </activeStateSelector>
                                    
                                </template>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- If we dont have any payment methods -->
                <div v-else class="clearfix">
                    
                    <!-- Show Refresh button -->
                    <basicButton size="default" icon="ios-refresh" class="float-right" :showIcon="true"
                        iconDirection="left" :ripple="true" rippleColor="blue"
                        @click.native="fetchPaymentMethods()">
                        <span>Refresh</span>
                    </basicButton>

                </div>

            </template>

        </Col>

    </Row>

</template>

<script>

    import activeStateSelector from './../../../../../activeStateSelector.vue';

    //  Get the loader
    import Loader from './../../../../../../../../../../../../../../components/_common/loaders/default.vue';

    /*  Buttons  */
    import basicButton from './../../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { activeStateSelector, Loader, basicButton },
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
            version: {
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
            paymentMethodsExist(){
                return (this.event.event_data.payment_methods || []).length ? true : false
            },
            paymentMethodsUrl(){
                console.log('this.version');
                console.log(this.version);
                console.log(this.version['_links']);
                console.log(this.version['_links']['sce:payment_methods']);
                console.log(this.version['_links']['sce:payment_methods'].href);
                return this.version['_links']['sce:payment_methods'].href;
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
                            self.payment_methods = ((data['_embedded'] || [])['payment_methods'] || []);

                            //  Update the billing payment methods
                            self.updateBillingPaymentMethods();

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
            },
            updateBillingPaymentMethods() {

                //  Get the billing payment methods
                var billing_payment_methods =
                this.payment_methods.map((payment_method) => {

                    var disabledMessage = (!payment_method['active']) ? 'This payment method is not available at the moment' : '';
                    
                    //  Get the matching payment methods selected for the Billing API
                    var event_payment_method_matches = this.event.event_data.payment_methods.filter( (event_payment_method) => { 
                        return (payment_method.type == event_payment_method.type);
                    });

                    //  Check if we have a matching payment method
                    var event_payment_method_exists = event_payment_method_matches.length ? true : false

                    //  If we have a matching payment method
                    if( event_payment_method_exists ){

                        //  Get the first matching payment method as the event payment method
                        var event_payment_method = event_payment_method_matches[0];

                        //  Set the disabled state and message
                        event_payment_method['disabled'] = !payment_method['active'];    //  Disable if not supported
                        event_payment_method['disabled_message'] = disabledMessage;       //   Show disabled message

                        //  Set the active state
                        event_payment_method['active'] = {
                            selected_type: 'yes',
                            code: ''
                        }
                        //  Return the payment method
                        return event_payment_method;

                    //  If we don't have a matching payment method
                    }else{

                        //  Set the disabled state and message
                        payment_method['disabled'] = !payment_method['active'];    //  Disable if not supported
                        payment_method['disabled_message'] = disabledMessage;       //   Show disabled message

                        //  Set the active state
                        payment_method['active'] = {
                            selected_type: 'yes',
                            code: ''
                        }

                        //  Return the current payment method as the event payment method
                        return payment_method;

                    }

                });
                
                //  Update the billing payment method
                this.$set(this.event.event_data, 'payment_methods', billing_payment_methods);
                
            }
        },
        created(){
            this.fetchPaymentMethods();
        }
    };
  
</script>