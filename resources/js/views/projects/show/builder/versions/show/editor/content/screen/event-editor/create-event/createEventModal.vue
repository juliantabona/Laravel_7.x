<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            width="600"
            title="Add Event"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <!-- Event List -->
            <div :style="{ minHeight: '220px' }">

                <transition name="fade" mode="out-in" appear>

                    <div :key="transitionTrigger">

                        <!-- Return Button -->
                        <Button v-if="showBackBtn" class="mb-2" @click.native="handleGoBack()">
                            <Icon type="ios-arrow-back" :size="20" />
                            <span>Back</span>
                        </Button>

                        <!-- Events -->
                        <Row :gutter="20">

                            <Col :span="8" v-for="(event, key) in displayEvents" :key="key" class="mb-2">
                                
                                <Card @click.native="handleSelectedEvent(event)" :padding="0">
                                    
                                    <div :style="{ padding: '14px' }">

                                        <!-- Event Icon -->
                                        <eventIcon :eventType="event.type" :size="30" class="text-center d-block"></eventIcon>
                                        
                                        <!-- Event Name -->
                                        <p class="text-center" style="padding-top:15px;">{{ event.type }}</p>

                                    </div>

                                </Card>

                            </Col>

                        </Row>

                    </div>

                </transition>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right">Close</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>
    
    import modalMixin from './../../../../../../../../../../../components/_mixins/modal/main.vue';
    import eventIcon from './../eventIcon.vue'

    export default {
        mixins: [modalMixin],
        components: { eventIcon },
        props: {
            events: {
                type: Array,
                default:() => []
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
            }
        },
        data(){

            return {
                showBackBtn: false,
                transitionTrigger: 1,
                primaryEvents: [
                    {
                        type: "API's",
                        children: [
                            {
                                type: "CRUD API"
                            },
                            {
                                type: "SMS API"
                            },
                            {
                                type: "Email API"
                            },
                            {
                                type: "Location API"
                            },
                            {
                                type: "Billing API"
                            },
                            {
                                type: "Subcription API"
                            }
                        ]
                    },
                    {
                        type: "Validation"
                    },
                    {
                        type: "Formatting"
                    },
                    {
                        type: "Local Storage"
                    },
                    {
                        type: "Custom Code"
                    },
                    {
                        type: "Revisit"
                    },
                    {
                        type: "Redirect"
                    }
                ],
                displayEvents: []
            }
        },
        computed: { 
            totalEvents(){
                return this.events.length;
            },
        },
        methods: {
            handleShowBackBtn(){
                this.showBackBtn = true;
            },
            handleHideBackBtn(){
                this.showBackBtn = false;
            },
            handleGoBack(){

                //  Show the primary events
                this.displayEvents = this.primaryEvents;
                
                //  Hide the Go Back Button
                this.handleHideBackBtn();

                //  Trigger the transition effect
                this.forceTransition();

            },
            forceTransition(){

                //  Trigger the transition effect
                this.transitionTrigger = ++this.transitionTrigger;

            },
            handleSelectedEvent(event){

                //  If this event has nested children events
                if( (event.children || []).length ){

                    //  Get the events to display
                    this.displayEvents = event.children;

                    //  Show the Go Back Button
                    this.handleShowBackBtn();

                    //  Trigger the transition effect
                    this.forceTransition();

                }else{

                    var newEvent = this.createEvent( event.type );
                    
                    this.events.push(newEvent);

                    this.$Message.success({
                        content: 'Event created!',
                        duration: 6
                    });

                    //  Close the modal
                    this.closeModal();

                }
            },
            createEvent( eventType ){

                var event = null;

                if( eventType == 'CRUD API' ){

                    event = this.get_CRUD_API_Event();

                }else if( eventType == 'SMS API' ){

                    event = this.get_SMS_API_Event();

                }else if( eventType == 'Email API' ){

                    event = this.get_Email_API_Event();

                }else if( eventType == 'Location API' ){

                    event = this.get_Location_API_Event();
                    
                }else if( eventType == 'Billing API' ){

                    event = this.get_Billing_API_Event();
                    
                }else if( eventType == 'Subcription API' ){

                    event = this.get_Subcription_API_Event();
                    
                }else if( eventType == 'Validation' ){

                    event = this.get_Validation_Event();
                    
                }else if( eventType == 'Formatting' ){

                    event = this.get_Formatting_Event();
                    
                }else if( eventType == 'Local Storage' ){

                    event = this.get_Local_Storage_Event();
                    
                }else if( eventType == 'Custom Code' ){

                    event = this.get_Custom_Code_Event();
                    
                }else if( eventType == 'Revisit' ){

                    event = this.get_Revisit_Event();
                    
                }else if( eventType == 'Redirect' ){

                    event = this.get_Redirect_Event();
                    
                }

                //  Overide the general event structure with the relevant event specific data
                return Object.assign({
                    id: this.generateEventId(),
                    type: eventType,
                    name: '',
                    active: {
                        text: true,
                        code_editor_text: '',
                        code_editor_mode: false
                    },
                    event_data: {},
                    hexColor: '#CECECE'

                }, event);
            },
            get_CRUD_API_Event(){
                
                return {
                    name: 'Create / Read / Update / Delete',
                    event_data: {
                        url: {
                            text: '',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        name: 'Get Items',
                        method: 'get',
                        trigger: 'on-enter',
                        query_params: [],
                        form_data_params: [],
                        headers: [],
                        response:{
                            general: {
                                default_success_message: {
                                    text: 'Completed successfully',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                default_error_message: {
                                    text:'Sorry, we are experiencing technical difficulties',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                            },
                            selected_type: 'automatic', //  automatic, manual
                            automatic: {
                                on_handle_success: 'use_default_success_msg',   //  do_nothing, use_default_success_msg
                                on_handle_error: 'use_default_error_msg',       //  do_nothing, use_default_error_msg
                            },
                            manual:{
                                response_status_handles: [
                                    {
                                        status: '200',
                                        reference_name: 'response',              //  e.g "response", "api_response", "api_data", 
                                        attributes: [
                                            {
                                                name: '', //  e.g items_response
                                                value: '{{ response }}'     //  e.g {{ response }}
                                            }
                                        ],
                                        on_handle: {
                                            selected_type: 'use_custom_msg',   //  do_nothing, use_custom_msg
                                            use_custom_msg: {
                                                text: '',
                                                code_editor_text: '',
                                                code_editor_mode: false
                                            }
                                        }
                                    }
                                ]
                            }
                        } 
                    }
                }
            },
            get_SMS_API_Event(){
                
                return {
                    name: 'Send SMS',
                    event_data: {

                    }
                }

            },
            get_Email_API_Event(){
                
                return {
                    name: 'Send Email',
                    event_data: {

                    }
                }

            },
            get_Location_API_Event(){
                
                return {
                    name: 'Get Location',
                    event_data: {

                    }
                }

            },
            get_Billing_API_Event(){
                
                return {
                    name: 'Handle Payment',
                    event_data: {

                    }
                }

            },
            get_Subcription_API_Event(){
                
                return {
                    name: 'Handle Subcription',
                    event_data: {

                    }
                }

            },
            get_Validation_Event(){
                
                return {
                    name: 'Validation',
                    event_data: {
                        target: {
                            text: '',              //  e.g "{{ product.quantity }}"
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        rules: []
                    }
                }

            },
            get_Formatting_Event(){
                
                return {
                    name: 'Formatting',
                    event_data: {
                        target: {
                            text: '',                   //  e.g "{{ product.quantity }}"
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        reference_name: '',             //  e.g "product_name"
                        rules: []
                    }
                }

            },
            get_Local_Storage_Event(){
                
                return {
                    name: 'Store Data',
                    event_data: {
                        reference_name: '',                     //  e.g "product_name"
                        storage: {
                            selected_type: 'array',             //  string, array, code
                            array: {
                                mode: {
                                    selected_type: 'append',    //  replace, append, prepend
                                },
                                dataset: {
                                    selected_type: 'values',     //  values, key_values=
                                    key_values: [],
                                    values: []
                                },
                            },
                            string: {
                                mode: {
                                    selected_type: 'replace',    //  replace, concatenate
                                    concatenate: {
                                        value: ','               
                                    }
                                },
                                dataset: {
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            },
                            code: {
                                mode: {
                                    selected_type: 'replace',    //  replace, append, prepend, concatenate
                                    concatenate: {
                                        value: ','               
                                    }
                                },
                                dataset: {
                                    value: ''
                                }
                            },
                        }
                    }
                }

            },
            get_Custom_Code_Event(){
                
                return {
                    name: 'Custom Code',
                    event_data: {

                    }
                }

            },
            get_Revisit_Event(){
                
                return {
                    name: 'Revisit',
                    event_data: {
                        general: {
                            trigger: {
                                selected_type: 'automatic',     //  automatic, manual
                                manual: {
                                    input: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                }
                            },
                            automatic_replies: {
                                text: '',
                                code_editor_text: '',
                                code_editor_mode: false
                            }
                        },
                        revisit_type: {
                            selected_type: 'home_revisit',      //  home_revisit, screen_revisit, marked_revisit
                            screen_revisit: {
                                link: {
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                            },
                            marked_revisit: {
                                selected_marker : ''
                            }
                        }
                    }
                }

            },
            get_Redirect_Event(){
                
                return {
                    name: 'Redirect',
                    event_data: {
                        general: {
                            trigger: {
                                selected_type: 'automatic',    //  automatic, manual
                                manual: {
                                    input: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                }
                            }
                        },
                        service_code: {
                            text: '',
                            code_editor_text: '',
                            code_editor_mode: false
                        }
                    }
                }

            },
            generateEventId(){
                return 'event_' + Date.now();
            }
        },
        created(){
            this.displayEvents = this.primaryEvents;
        }
    }
</script>