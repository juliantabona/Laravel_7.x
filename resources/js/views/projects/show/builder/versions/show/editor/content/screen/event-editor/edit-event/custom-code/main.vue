<template>

    <div>

        <!-- Custom Code Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Custom Code</span> to write PHP Code
            to execute
        </Alert>

        <!-- Code Editor -->
        <customEditor
            :useCodeEditor="true"
            :codeContent="event.event_data.code"
            @codeChange="event.event_data.code = $event"
            sampleCodeTemplate="ussd_service_instructions_sample_code">
        </customEditor>

    </div>

</template>

<script>

    import customEditor from './../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
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

            }
        }
    }
</script>