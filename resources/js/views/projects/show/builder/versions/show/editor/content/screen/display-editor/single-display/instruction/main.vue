<template>

    <div>
        
        <!-- Display Instruction Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Write the <span class="font-italic text-success font-weight-bold">Screen Instructions</span>.
            This is the main information you want to display on the screen such as instructions or dynamic information.
        </Alert>

        <!-- Use Code Editor Swtich -->
        <div class="mt-2 mb-2">
            <Poptip trigger="hover" width="350" placement="right" word-wrap>

                <template slot="content">
                    <span class="d-block">Use the Code Editor to write code in PHP and edit your screen with full control</span>
                
                    <span style="margin-top: -15px;" class="border-top d-block pt-2">
                        <span class="font-weight-bold">Note:</span> Always wrap strings in <span class="text-primary">single quotes ('')</span> and use the <span class="text-primary">period (.)</span> to concatenat values. Use <span class="text-primary">double pipe (||)</span> or <span class="text-primary">("\n")</span> for line-breaks. Make sure to include the <span class="text-primary">return</span> statement as soon as you want to output your result. Keep all your code within PHP Tags <span class="text-primary"><?php ?></span>
                    </span>
                </template>

                <Icon type="ios-code" class="border rounded-circle p-1" :size="20" />
                <span class="font-weight-bold text-dark">Use Code Editor: </span>
                <i-switch 
                    size="small"
                    class="ml-1"
                    :disabled="false"
                    true-color="#13ce66" 
                    false-color="#ff4949" 
                    :value="display.content.description.code_editor_mode" 
                    @on-change="display.content.description.code_editor_mode = $event">
                </i-switch>

            </Poptip>
        </div>

        <!-- Display Instruction Input -->
        <customEditor
            :content="display.content.description.text"
            sampleCodeTemplate="ussd_service_instructions_sample_code"
            @contentChange="display.content.description.text = $event"
            :codeContent="display.content.description.code_editor_text"
            :useCodeEditor="display.content.description.code_editor_mode"
            @codeChange="display.content.description.code_editor_text = $event"
            :placeholder="'Welcome to {{ company.name }}, how may we help you today?'">
        </customEditor>
        
    </div>

</template>

<script>

    import customEditor from '../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
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