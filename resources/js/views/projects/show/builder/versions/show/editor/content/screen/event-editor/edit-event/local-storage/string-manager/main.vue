<template>

    <!-- Value --> 
    <textOrCodeEditor
        size="small"
        class="mt-2 mb-2"
        :placeholder="','"
        title="String / Text"
        :value="event.event_data.storage.string.dataset"
        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
    </textOrCodeEditor>

</template>

<script>
    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
        props: { 
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
            builder: {
                type: Object,
                default: () => {}
            },
        },
        data(){
            return {
                
            }
        }
    };
  
</script>