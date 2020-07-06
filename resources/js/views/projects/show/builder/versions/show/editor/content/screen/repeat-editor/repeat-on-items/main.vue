<template>
       
    <Row :gutter="4">

        <Col :span="24">

            <Row :gutter="4" class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
        
                <!-- Foreach Items Group Reference -->
                <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12">
                
                    <!-- Group Reference -->
                    <textOrCodeEditor
                        size="small"
                        title="Foreach"
                        :placeholder="'{{ items }}'"
                        :value="screen.repeat.repeat_on_items.group_reference"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                    </textOrCodeEditor>

                </Col>
        
                <!-- As Label -->
                <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12" class="d-flex pt-4">

                    <span class="d-block text-center mt-1 mr-3">As</span>
                
                    <!-- Template Reference Name Input -->
                    <referenceNameInput 
                        v-model="screen.repeat.repeat_on_items.item_reference_name" class="w-100"
                        :builder="builder" :screen="screen" :inlineLayout="false" :isRequired="false">
                    </referenceNameInput>
                
                </Col>

            </Row>

        </Col>
        
        <Col :span="24">

            <!-- Additional References -->
            <span class="d-block font-weight-bold text-dark">Additional References</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">

                <Row :gutter="4">

                    <Col :span="12">
                
                        <!-- Total Items Reference Name -->
                        <referenceNameInput 
                            v-model="screen.repeat.repeat_on_items.total_loops_reference_name"
                            :builder="builder" :screen="screen" title="Total Items" 
                            :inlineLayout="false" :isRequired="false">
                        </referenceNameInput>

                    </Col>

                    <Col :span="12">
                
                        <!-- Item Index Reference Name -->
                        <referenceNameInput 
                            v-model="screen.repeat.repeat_on_items.loop_index_reference_name"
                            :builder="builder" :screen="screen" title="Item Index" 
                            :inlineLayout="false" :isRequired="false">
                        </referenceNameInput>

                    </Col>

                    <Col :span="12">
                
                        <!-- Item Number Reference Name -->
                        <referenceNameInput 
                            v-model="screen.repeat.repeat_on_items.loop_number_reference_name"
                            :builder="builder" :screen="screen" title="Item Number"
                            :inlineLayout="false" :isRequired="false">
                        </referenceNameInput>

                    </Col>

                    <Col :span="12">
                
                        <!-- Item Number Reference Name -->
                        <referenceNameInput 
                            v-model="screen.repeat.repeat_on_items.is_first_loop_reference_name"
                            :builder="builder" :screen="screen" title="Is First Item"
                            :inlineLayout="false" :isRequired="false">
                        </referenceNameInput>

                    </Col>

                    <Col :span="12">
                
                        <!-- Is Last Item Reference Name -->
                        <referenceNameInput 
                            v-model="screen.repeat.repeat_on_items.is_last_loop_reference_name"
                            :builder="builder" :screen="screen" title="Is Last Item"
                            :inlineLayout="false" :isRequired="false">
                        </referenceNameInput>

                    </Col>

                </Row>

            </div>

            <!-- No Loops Behaviour -->
            <span class="d-block font-weight-bold text-dark mb-2">No Loops Behaviour</span>

            <Row :gutter="12" class="bg-grey-light border mb-3 p-2 clearfix">

                <Col :span="onNoLoopBehaviourIsExpanded ? 24 : 12">

                    <div class="d-flex">

                        <span class="font-weight-bold text-dark mt-1 mr-2">Behaviour:</span>
                                
                        <!-- Behaviour Method -->
                        <Select v-model="screen.repeat.repeat_on_items.on_no_loop.selected_type">
                            <Option v-for="(behaviour_type, index) in behaviour_types" :value="behaviour_type.value" :key="index">
                                {{ behaviour_type.name }}
                            </Option>
                        </Select>
                    
                    </div>

                </Col>

                <template v-if="screen.repeat.repeat_on_items.on_no_loop.selected_type == 'link'">

                    <Col :span="onNoLoopBehaviourIsExpanded ? 24 : 12" :class="[onNoLoopBehaviourIsExpanded ? 'mt-2': '']">

                        <!-- Select Screen Link -->
                        <screenAndDisplaySelector 
                            :link="screen.repeat.repeat_on_items.on_no_loop.link"
                            :builder="builder" :screen="screen" :showDisplays="false">
                        </screenAndDisplaySelector>

                    </Col>
                    
                </template>

            </Row>

            <!-- After Last Loop Behaviour -->
            <span class="d-block font-weight-bold text-dark mb-2">After Last Loop Behaviour</span>
            
            <Row :gutter="12" class="bg-grey-light border mb-3 p-2 clearfix">

                <Col :span="afterLastLoopBehaviourIsExpanded ? 24 : 12">

                    <div class="d-flex">

                        <span class="font-weight-bold text-dark mt-1 mr-2">Behaviour:</span>
                                
                        <!-- Behaviour Method -->
                        <Select v-model="screen.repeat.repeat_on_items.after_last_loop.selected_type">
                            <Option v-for="(behaviour_type, index) in behaviour_types" :value="behaviour_type.value" :key="index">
                                {{ behaviour_type.name }}
                            </Option>
                        </Select>
                    
                    </div>

                </Col>

                <template v-if="screen.repeat.repeat_on_items.after_last_loop.selected_type == 'link'">

                    <Col :span="afterLastLoopBehaviourIsExpanded ? 24 : 12" :class="[afterLastLoopBehaviourIsExpanded ? 'mt-2': '']">

                        <!-- Select Screen Link -->
                        <screenAndDisplaySelector 
                            :link="screen.repeat.repeat_on_items.after_last_loop.link"
                            :builder="builder" :screen="screen" :showDisplays="false">
                        </screenAndDisplaySelector>

                    </Col>

                </template>

            </Row>
        
        </Col>

    </Row>

</template>

<script>

    import screenAndDisplaySelector from './../../screenAndDisplaySelector.vue';
    import referenceNameInput from './../../referenceNameInput.vue';
    import textOrCodeEditor from './../../textOrCodeEditor.vue';
    
    export default {
        components: { screenAndDisplaySelector, referenceNameInput, textOrCodeEditor },
        props: { 
            screen:{
                type: Object,
                default: () => {}
            },
            builder: {
                type: Object,
                default: () => {}
            }
        },
        data(){
            return {
                behaviour_types: [
                    {
                        name: 'Do Nothing',
                        value: 'do_nothing'
                    },
                    {
                        name: 'Link To Screen',
                        value: 'link'
                    }
                ]
            }
        },
        computed: {
            groupReferenceUsesCodeEditorMode(){
                return this.screen.repeat.repeat_on_items.group_reference.code_editor_mode;
            },
            onNoLoopBehaviourIsExpanded(){
                return ( this.screen.repeat.repeat_on_items.on_no_loop.selected_type == 'link' && 
                         this.screen.repeat.repeat_on_items.on_no_loop.link.code_editor_mode )
            },
            afterLastLoopBehaviourIsExpanded(){
                return ( this.screen.repeat.repeat_on_items.after_last_loop.selected_type == 'link' && 
                         this.screen.repeat.repeat_on_items.after_last_loop.link.code_editor_mode )
            }
        }
    };
  
</script>