<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Array Value Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <span v-if="arrayValue.value.code_editor_mode">

                        <Icon type="ios-code" size="20" />
                        <span>Custom Code</span>

                    </span>
                    
                    <template v-else>
                        <span class="single-draggable-item-title d-block font-weight-bold cut-text" v-html="arrayValue.value.text"></span>
                    </template>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="arrayValue.active.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ arrayValue.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Value: </span>
                                <span v-if="arrayValue.value.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text" v-html="arrayValue.value.text"></span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Default: </span>
                                <template v-if="arrayValue.on_empty_value.default.selected_type == 'custom'">
                                    <span v-if="arrayValue.on_empty_value.default.custom.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span class="cut-text" v-html="arrayValue.on_empty_value.default.custom.text"></span>
                                    </template>
                                </template>
                                <span v-else class="cut-text">{{ defaultType }}</span>
                            </ListItem>

                            <ListItem v-if="arrayValue.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ arrayValue.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidArrayValue" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="arrayValue.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="arrayValue.active.text ? 'green' : 'warning'">{{ arrayValue.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Array Value Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Array Value Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveArrayValue()" />

                <!-- Edit Array Value Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditArrayValue()" />

                <!-- Copy Array Value Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneArrayValue()"/>

                <!-- Move Array Value Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Array Value Details  -->
                
        </div>

        <!-- 
            MODAL TO CLONE / EDIT KEY/VALUE
        -->
        <template v-if="isOpenManageArrayValueModal">

            <manageArrayValueModal
                :index="index"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :arrayValue="arrayValue"
                :arrayValues="arrayValues"
                @visibility="isOpenManageArrayValueModal = $event">
            </manageArrayValueModal>

        </template>

    </Card>

</template>

<script>

    import manageArrayValueModal from './../manageArrayValueModal.vue';

    var localCustomMixin = require('./../../localMixin.js').default;

    export default {
        components: { manageArrayValueModal },
        mixins: [localCustomMixin],
        props: {
            index: {
                type: Number,
                default:null
            },
            arrayValue: {
                type: Object,
                default:() => {}
            },
            arrayValues: {
                type: Array,
                default:() => []
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
            }
        },
        data(){

            return {
                isOpenManageArrayValueModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.arrayValue.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
            },
            isValidArrayValue(){
                return true;
            },
            defaultType(){
                //  Note: defaultTypes is registered within localMixin.js
                for (let index = 0; index < this.defaultTypes.length; index++) {
                    if( this.defaultTypes[index].value ==  this.arrayValue.on_empty_value.default.selected_type){
                        return this.defaultTypes[index].name;
                    }
                }
            }
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditArrayValue(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageArrayValueModal();
            },
            handleCloneArrayValue() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageArrayValueModal();
            },
            handleConfirmRemoveArrayValue(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the Array Value removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Array Value',
                    onOk: () => { this.handleRemoveArrayValue() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.arrayValue.name),
                                '". After this array value is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveArrayValue() {

                //  Remove Array Value from list
                this.arrayValues.splice(this.index, 1);

                //  Array Value removed success message
                this.$Message.success({
                    content: 'Array Array Value removed!',
                    duration: 6
                });
            },
            handleOpenManageArrayValueModal(){
                this.isOpenManageArrayValueModal = true;
            }
        }
    }

</script>
