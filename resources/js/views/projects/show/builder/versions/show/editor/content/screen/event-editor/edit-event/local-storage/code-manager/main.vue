<template>

    <div class="mt-2">
        
        <span class="d-block font-weight-bold text-dark mb-2">Code:</span>

        <!-- Code Editor --> 
        <customEditor
            :useCodeEditor="true"
            size="small" class="w-100" classes="px-1"
            sampleCodeTemplate="ussd_service_local_storage_sample_code"
            :codeContent="event.event_data.storage.code.dataset.value"
            @codeChange="event.event_data.storage.code.dataset.value = $event">
        </customEditor>

    </div>

</template>

<script>

    import customEditor from './../../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
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