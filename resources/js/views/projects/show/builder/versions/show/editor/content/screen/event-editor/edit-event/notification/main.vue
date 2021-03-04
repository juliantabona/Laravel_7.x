<template>

    <div>

        <!-- Auto Reply Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Notification</span>
            to show a brief message to the user.
        </Alert>

        <Row :gutter="12" class="mt-2">

            <Col :span="24">

                <!-- Message Input -->
                <textOrCodeEditor
                    size="medium"
                    class="mt-2 mb-2"
                    title="Message"
                    :value="event.event_data.message"
                    :placeholder="'Hi {{ first_name }}, your account was created!'"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>

                <!-- Continue Input -->
                <textOrCodeEditor
                    size="small"
                    class="mt-2 mb-2"
                    title="Continue Text"
                    :placeholder="'Continue'"
                    :value="event.event_data.continue_text"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>

                <!-- MSISDN Input -->
                <textOrCodeEditor
                    size="small"
                    class="mt-2 mb-2"
                    title="Mobile Number"
                    :placeholder="'Continue'"
                    :value="event.event_data.msisdn"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>

            </Col>

        </Row>

    </div>

</template>

<script>

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
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
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {

            }
        }
    }
</script>
