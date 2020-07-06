<template>

    <div>
        
        <!-- Display Instruction Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Write the <span class="font-italic text-success font-weight-bold">Screen Instructions</span>.
            This is the main information you want to display on the screen such as instructions or dynamic information.
        </Alert>

        <!-- Instruction -->
        <textOrCodeEditor
            class="mb-2"
            size="medium"
            icon="ios-code"
            title="Instruction"
            :value="display.content.description"
            sampleCodeTemplate="ussd_service_instructions_sample_code"
            :placeholder="'Welcome to {{ company.name }}, how may we help you today?'">
        </textOrCodeEditor>
        
    </div>

</template>

<script>

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
        props: { 
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
        }
    };
  
</script>