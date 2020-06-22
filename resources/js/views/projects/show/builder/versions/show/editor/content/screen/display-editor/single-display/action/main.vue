<template>

    <div>
        
        <!-- Display Action Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Select the <span class="font-italic text-success font-weight-bold">Screen Action</span>.
            The screen action is what you expect the user to do while viewing this screen e.g provide an 
            <span class="font-italic text-success font-weight-bold">Input Value</span> such as their Full Name or
            <span class="font-italic text-success font-weight-bold">Select Option</span> from a list of specified options.
        </Alert>

        <Row :gutter="10">

            <!-- Primary Action Separator -->
            <Col :span="12" class="d-flex">
            
                <span class="d-block font-weight-bold text-dark mt-2 mr-2">Action: </span>
                
                <Select v-model="display.content.action.selected_type" 
                        class="w-50 mb-2" placeholder="Action type">
                    
                    <Option v-for="(action, key) in actionsTypes" :key="key" class="mb-2"
                            :value="action.type" :label="action.name">
                    </Option>

                </Select>

            </Col>

            <!-- Secondary Action Separator -->
            <Col v-if="display.content.action.selected_type != 'no_action'" :span="12" class="d-flex" >
                    
                <span class="d-block font-weight-bold text-dark mt-2 mr-2">Type: </span>

                <!-- Input Secondary Action Selector -->
                <Select v-if="display.content.action.selected_type == 'input_value'"
                        v-model="display.content.action.input_value.selected_type"
                        class="mb-2" placeholder="Input value type">
                    
                    <Option v-for="(action, key) in inputActionTypes" :key="key" class="mb-2"
                            :value="action.type" :label="action.name">
                    </Option>

                </Select>
                
                <!-- Select Option Secondary Action Selector -->
                <Select v-if="display.content.action.selected_type == 'select_option'"
                        v-model="display.content.action.select_option.selected_type"
                        class="mb-2" placeholder="Select option type">
                    
                    <Option v-for="(action, key) in selectOptionActionTypes" :key="key" class="mb-2"
                            :value="action.type" :label="action.name">
                    </Option>

                </Select>

            </Col>

            <Col :span="24">

                <!-- Input Value Actions  -->
                <template v-if="display.content.action.selected_type == 'input_value'">
                    
                    <!-- Single Value Input Manager -->
                    <singleValueInputManager 
                        v-if="display.content.action.input_value.selected_type == 'single_value_input'" 
                        :builder="builder" :screen="screen" :display="display">
                    </singleValueInputManager>

                    <!-- Multi Value Input Manager -->
                    <multiValueInputManager 
                        v-if="display.content.action.input_value.selected_type == 'multi_value_input'" 
                        :builder="builder" :screen="screen" :display="display">
                    </multiValueInputManager>

                </template>

                <!-- Select Option Actions  -->
                <div v-if="display.content.action.selected_type == 'select_option'"
                          class="border-top-dashed pt-3 mt-2">
                    
                    <!-- Static Select Options Manager -->
                    <staticOptionsManager 
                        v-if="display.content.action.select_option.selected_type == 'static_options'" 
                        :builder="builder" :screen="screen" :display="display">
                    </staticOptionsManager>

                    <!-- Dynamic Select Options Manager -->
                    <dynamicOptionsManager 
                        v-if="display.content.action.select_option.selected_type == 'dynamic_options'" 
                        :builder="builder" :screen="screen" :display="display">
                    </dynamicOptionsManager>

                    <!-- Code Select Options Manager -->
                    <codeOptionsManager 
                        v-if="display.content.action.select_option.selected_type == 'code_editor_options'" 
                        :builder="builder" :screen="screen" :display="display">
                    </codeOptionsManager>

                </div>

            </Col>

        </Row>
        
    </div>

</template>

<script>

    //  Get the single value input manager
    import singleValueInputManager from './input-value/singleValueInputManager.vue';

    //  Get the multi value input manager
    import multiValueInputManager from './input-value/multi-input/multiValueInputManager.vue';

    //  Get the static options manager
    import staticOptionsManager from './select-option/static-options/main.vue';

    //  Get the dynamic options manager
    import dynamicOptionsManager from './select-option/dynamicOptionsManager.vue';

    //  Get the code options manager
    import codeOptionsManager from './select-option/codeOptionsManager.vue';

    export default {
        components: { 
            singleValueInputManager, multiValueInputManager, staticOptionsManager, dynamicOptionsManager, codeOptionsManager
        },
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
        },
        data(){
            return {
                actionsTypes: [
                    {
                        name: 'No Action', type: 'no_action'
                    },
                    {
                        name: 'Input Value', type: 'input_value'
                    },
                    {
                        name: 'Select Option', type: 'select_option'
                    }
                ],
                inputActionTypes: [
                    {
                        name: 'Single Input', type: 'single_value_input'
                    },
                    {
                        name: 'Multiple Inputs', type: 'multi_value_input'
                    }
                ],
                selectOptionActionTypes: [
                    {
                        name: 'Static Options', type: 'static_options'
                    },
                    {
                        name: 'Dynamic Options', type: 'dynamic_options'
                    },
                    {
                        name: 'Code Editor Options', type: 'code_editor_options'
                    }
                ],
            }
        }
    };
  
</script>