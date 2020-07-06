<template>

    <div>

        <div class="d-flex align-items-center mb-1">
            
            <!-- Icon -->
            <Icon v-if="icon" :type="icon" class="border rounded-circle p-1 mr-1" :size="20" />

            <!-- If the title is passed as a prop -->
            <span v-if="title" class="font-weight-bold">{{ title }}</span>

            <!-- If the title is passed as a named slot -->
            <slot name="title"></slot>

            <Poptip trigger="hover" width="350" placement="right" word-wrap>

                <template slot="content">
                    <span class="d-block">Use the Code Editor to write code in PHP</span>
                
                    <span style="margin-top: -15px;" class="border-top d-block pt-2">
                        <span class="font-weight-bold">Note:</span> Always wrap strings in <span class="text-primary">single quotes ('')</span> and use the <span class="text-primary">period (.)</span> to concatenat values. Use <span class="text-primary">double pipe (||)</span> or <span class="text-primary">("\n")</span> for line-breaks. Make sure to include the <span class="text-primary">return</span> statement as soon as you want to output your result. Keep all your code within PHP Tags <span class="text-primary"><?php ?></span>
                    </span>
                </template>
                
                <i-switch 
                    size="small"
                    class="ml-1"
                    :disabled="false"
                    true-color="#13ce66" 
                    false-color="#ff4949" 
                    :value="value.code_editor_mode" 
                    @on-change="value.code_editor_mode = $event">
                </i-switch>

            </Poptip>

        </div>

        <!-- Screen /  Display Code Selector -->
        <customEditor
            :size="size"
            :content="value.text"
            :placeholder="placeholder"
            @contentChange="value.text = $event"
            :codeContent="value.code_editor_text"
            :useCodeEditor="value.code_editor_mode"
            :sampleCodeTemplate="sampleCodeTemplate"
            @codeChange="value.code_editor_text = $event">
        </customEditor>

    </div>

</template>

<script>

    import customEditor from './../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            size: {
                type: String,
                default: null
            },
            title: {
                type: String,
                default: null
            },
            value: {
                type: Object,
                default: null
            },
            placeholder: {
                type: String,
                default: null
            },
            sampleCodeTemplate: {
                type: String,
                default: null
            },
            icon: {
                type: String,
                default: null
            }
        },
        data(){
            return {
            }
        }
    }
</script>
