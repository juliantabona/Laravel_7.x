<template>


    <div >

        <div class="d-flex">

            <!-- If the title is passed as a prop -->
            <span v-if="title" class="font-weight-bold mt-1 mr-2" :style="titleStyle">{{ title }}</span>

            <!-- If the title is passed as a named slot -->
            <slot name="title"></slot>

            <div class="w-100">

                <Select v-model="value.selected_type" :disabled="disabled" class="w-100">
                    <Option v-for="(option, index) in options"
                            :value="option.value" :key="index">
                        {{ option.name }}
                    </Option>
                </Select>

                <!-- If this is disabled and we have a disabled message -->
                <span v-if="disabled && disabledMessage" class="text-info font-weight-bold d-block mt-1">
                    <Icon type="ios-information-circle-outline" class="mr-1" :size="20" />
                    <span>{{ disabledMessage }}</span>
                </span>

            </div>

        </div>

        <template v-if="value.selected_type == 'conditional' && !disabled">

            <!-- Code Editor -->
            <customEditor
                class="mt-2"
                :useCodeEditor="true"
                :codeContent="value.code"
                @codeChange="value.code = $event"
                sampleCodeTemplate="ussd_service_instructions_sample_code">
            </customEditor>

        </template>

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
            disabled: {
                type: Boolean,
                default: false
            },
            disabledMessage: {
                type: String,
                default: ''
            },
            title: {
                type: String,
                default: 'Active:'
            },
            titleStyle: {
                type: Object,
                default: null
            },
            value: {
                type: Object,
                default: null
            },
            sampleCodeTemplate: {
                type: String,
                default: null
            },
            activeStateOptions: {
                type: Array,
                default: function(){
                    return ['Yes', 'No', 'Conditional']
                }
            }
        },
        data(){
            return {

            }
        },
        computed: {
            options(){
                return this.activeStateOptions.map((option) => {
                    return {
                        name: option,                   //  Yes
                        value: option.toLowerCase()     //  yes
                    }
                });
            }
        }
    }
</script>
