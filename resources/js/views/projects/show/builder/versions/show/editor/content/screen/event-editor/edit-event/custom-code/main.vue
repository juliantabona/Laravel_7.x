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
            sampleCodeTemplate="ussd_service_custom_code_event_sample_code">
        </customEditor>

        <!-- Code Methods -->
        <Table :columns="columns" :data="data" class="mb-5" size="small" max-height="300"></Table>

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
                data: [
                    {
                        name: '$this->logInfo(\'My message\')',
                        desc: 'Log information on the Simulator debugger. The input is the message string.'
                    },
                    {
                        name: '$this->logWarning(\'My Warning\')',
                        desc: 'Log a warning on the Simulator debugger. The input is the warning string.'
                    },
                    {
                        name: '$this->logError(\'My Error\')',
                        desc: 'Log an error on the Simulator debugger. The input is the message error.'
                    },
                    {
                        name: '$this->getTotalScreenResponses(\'screen_1603621400274\')',
                        desc: 'Count the number of responses to the givent screen. The input is the screen id'
                    },
                    {
                        name: '$this->getTotalDisplayResponses(\'display_1603621405919\')',
                        desc: 'Count the number of responses to the givent display. The input is the display id'
                    },
                    {
                        name: '$this->hasResponded()',
                        desc: 'Check if the user has responded to the current level (screen or display)'
                    }


                ],
                columns: [
                    {
                        title: 'Code',
                        key: 'name'
                    },
                    {
                        title: 'Description',
                        key: 'desc'
                    }
                ],

            }
        }
    }
</script>
