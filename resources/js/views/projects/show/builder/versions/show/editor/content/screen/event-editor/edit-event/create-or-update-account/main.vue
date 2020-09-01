<template>

    <div>

        <!-- Linking Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use this event to <span class="font-italic text-success font-weight-bold">Create</span> a new user profile
            or to <span class="font-italic text-success font-weight-bold">Update</span> an already existing user
            profile
        </Alert>

        <Row class="bg-grey-light border mt-2 mb-3 py-3 px-2">
            
            <Col :span="24">
                    
                <textOrCodeEditor
                    class="mb-2"
                    title="First Name"
                    :value="event.event_data.first_name"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>
                    
                <textOrCodeEditor
                    class="mb-2"
                    title="Last Name"
                    :value="event.event_data.last_name"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>
                    
                <textOrCodeEditor
                    title="Mobile Number"
                    :value="event.event_data.mobile_number"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>

            </Col>

        </Row>

        <span class="font-weight-bold text-dark d-block mb-2 mr-2">Additional Fields</span>

        <!-- Key/Value Manager -->
        <additionalKeyValueManager v-bind="$props"></additionalKeyValueManager>

    </div>

</template>

<script>

    import additionalKeyValueManager from './additional-key-value-manager/main.vue'
    import textOrCodeEditor from './../../../textOrCodeEditor.vue';

    export default {
        components: { additionalKeyValueManager, textOrCodeEditor },
        props:{
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            version: {
                type: Object,
                default: () => {}
            }
        },
        data(){
            return{
                triggerTypes: [
                    {
                        name: 'Automatic',
                        value: 'automatic'
                    },
                    {
                        name: 'Manual',
                        value: 'manual'
                    }
                ]
            }
        },
        computed: {
            firstRowSpan(){

                if( this.event.event_data.link.code_editor_mode ){

                    return 24;

                }

                return 12;

            }
        },
    }
</script>