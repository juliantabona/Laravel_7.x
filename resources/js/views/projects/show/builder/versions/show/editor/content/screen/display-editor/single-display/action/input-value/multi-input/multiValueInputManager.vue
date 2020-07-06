<template>

    <Row>

        <Col :span="24" class="bg-grey-light border mt-3 p-2">

            <Row>  

                <Col :span="24">
                
                    <!-- Multi Value Separator -->
                    <div class="d-flex">
                        <span class="d-block font-weight-bold text-dark mt-2 mr-2">Separator: </span>
                            
                        <Select v-model="display.content.action.input_value.multi_value_input.separator" 
                                class="mb-2" style="width: 200px;" placeholder="Select separator">
                            
                            <Option v-for="(separator, key) in separatorTypes" :key="key" class="mb-2"
                                    :value="separator.type" :label="separator.name">
                            </Option>

                        </Select>
                    </div>

                    <template v-if="display.content.action.input_value.multi_value_input.reference_names.length">
                    
                        <!-- Foreach Multi Value Input Reference Name -->
                        <Row :gutter="4" v-for="(reference_name, index) in display.content.action.input_value.multi_value_input.reference_names" :key="index">

                            <Col :span="22">
                            
                                <!-- Multi Value Reference Name Input -->
                                <referenceNameInput 
                                    v-model="display.content.action.input_value.multi_value_input.reference_names[index]"
                                    :referenceNames="display.content.action.input_value.multi_value_input.reference_names"
                                    :display="display"
                                    :builder="builder"
                                    :screen="screen"
                                    :index="index">
                                </referenceNameInput>

                            </Col>
                            
                            <Col :span="2">

                                <!-- Remove Option Button  -->
                                <Poptip confirm title="Are you sure you want to remove this option?" 
                                        ok-text="Yes" cancel-text="No" width="300" @on-ok="removeMultiInputReference(index)"
                                        placement="top-end">
                                    <Icon type="ios-trash-outline" class="screen-icon hidable mr-2" size="20"/>
                                </Poptip>

                            </Col>

                        </Row>

                    </template>

                    <Alert v-else type="info" class="mb-2" show-icon>No references</Alert>

                </Col>

                <Col :span="22">

                    <!-- Add Static Option -->
                    <div class="clearfix">

                        <!-- Add Static Option Button -->
                        <Button class="float-right" @click.native="handleOpenAddReferenceNameModal()">
                            <Icon type="ios-add" :size="20" />
                            <span>Add Reference</span>
                        </Button>

                    </div>

                </Col>

            </Row>

        </Col>

        <Col :span="24" class="bg-grey-light border mt-2 p-2">
            
            <!-- Next Screen Selector -->
            <screenAndDisplaySelector 
                :link="display.content.action.input_value.multi_value_input.link" 
                :builder="builder" :screen="screen" :display="display">
            </screenAndDisplaySelector>

        </Col>

        <!-- 
            MODAL TO ADD NEW REFERENCE NAME
        -->
        <template v-if="isOpenAddReferenceNameModal">

            <addReferenceNameModal
                :builder="builder" :screen="screen" :display="display"
                @visibility="isOpenAddReferenceNameModal = $event">
            </addReferenceNameModal>

        </template>

    </Row>
    
</template>

<script>

    import addReferenceNameModal from './addReferenceNameModal.vue';
    import referenceNameInput from './../../../../../referenceNameInput.vue';
    import screenAndDisplaySelector from './../../../../../screenAndDisplaySelector.vue';

    export default {
        components: { addReferenceNameModal, referenceNameInput, screenAndDisplaySelector },
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
                isOpenAddReferenceNameModal: false,
                separatorTypes: [
                    {
                        name: 'Single spaces ( )', type: 'spaces'
                    },
                    {
                        name: 'Period symbol (.)', type: '.'
                    },
                    {
                        name: 'Comma symbol (,)', type: ','
                    },
                    {
                        name: 'Hyphen symbol (-)', type: '-'
                    },
                    {
                        name: 'Plus symbol (+)', type: '+'
                    },
                    {
                        name: 'Hash symbol (#)', type: ' '
                    },
                    {
                        name: 'Forward slash symbol (/)', type: '/'
                    }
                ]
            }
        }, 
        methods: {
            handleOpenAddReferenceNameModal(index) {
                this.isOpenAddReferenceNameModal = true;
            },
            addMultiInputReference(){

                //  Build the multi-input reference name template
                var template = '';

                //  Add to existing reference names
                this.display.content.action.input_value.multi_value_input.reference_names.push( template );

            },
            removeMultiInputReference(index){

                //  Remove current reference name
                this.display.content.action.input_value.multi_value_input.reference_names.splice(index, 1);

                //  Reference name removed success message
                this.$Message.success({
                    content: 'Reference name removed!',
                    duration: 6
                });

            }
        }
    };
  
</script>