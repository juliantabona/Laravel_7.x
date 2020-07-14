<template>

    <Card :bordered="false" :style="{ overflow: 'inherit' }">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Conditional Screens</span>
        </Divider>
        
        <!-- Display Instruction Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Conditional Screens</span> to write PHP Code
            that will determine which screen to load first. This means that you can decide before hand which screen
            to load first based on a condition.
        </Alert>

        <span class="d-block mt-4 mb-4">
            <span class="font-weight-bold mr-1">Active:</span>
            <i-Switch v-model="builder.conditional_screens.active" />
        </span>

        <template v-if="builder.conditional_screens.active">

            <!-- Code Editor -->
            <customEditor
                :useCodeEditor="true"
                :codeContent="builder.conditional_screens.code"
                @codeChange="builder.conditional_screens.code = $event"
                sampleCodeTemplate="ussd_service_instructions_sample_code">
            </customEditor>

        </template>

    </Card>

</template>

<script>

    import customEditor from './../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            builder: {
                type: Object,
                default: null
            },
        },
        data(){

            return {
                
            }
        }
    }
</script>
