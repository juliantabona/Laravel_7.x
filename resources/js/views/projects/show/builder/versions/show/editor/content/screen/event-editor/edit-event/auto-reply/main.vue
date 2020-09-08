<template>

    <div>

        <!-- Auto Reply Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Auto Reply</span> 
            to reply on behalf of the user.
        </Alert>

        <!-- Automatic Replies Input --> 
        <textOrCodeEditor
            size="small"
            class="mt-2 mb-2"
            title="Automatic Replies"
            :placeholder="'1*2*3*{{ order.id }}*4'"
            :value="event.event_data.automatic_replies"
            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
        </textOrCodeEditor>

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