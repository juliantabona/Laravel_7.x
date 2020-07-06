<template>

    <Card :bordered="false" :style="{ overflow: 'inherit' }">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Global Variables</span>
        </Divider>

        <div class="clearfix mb-4">

            <!-- Add Variable Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                         class="float-right" iconDirection="left" :ripple="!variablesExist"
                         @click.native="handleAddVariable()">
                <span>Add Variable</span>
            </basicButton>

        </div>

        <!-- Global Variable Settings -->
        <Table :columns="columns" :data="builder.global_variables" :style="{ overflow: 'inherit' }" 
                class="mb-5" size="small" max-height="300" no-data-text="No Variables Found">

            <!-- Property Name -->
            <template slot-scope="{ row, index }" slot="name">
                
                <Input v-model="row.name" size="small" placeholder="Variable name..." maxlength="50" class="w-100" 
                        @on-change="updateVariableData(row, index)" />

            </template>

            <!-- Property Type -->
            <template slot-scope="{ row, index }" slot="type">
                
                <!-- Data Type Selector -->
                <Select v-model="row.type" size="small" class="w-100" @on-change="updateVariableData(row, index)">
                    <Option v-for="dataType in dataTypes" :value="dataType" :key="dataType">{{ dataType }}</Option>
                </Select>

            </template>

            <!-- Property Value -->
            <template slot-scope="{ row, index }" slot="value">
                
                <!-- String Input Value -->
                <Input v-if="row.type == 'String'" v-model="row.value.string" size="small" placeholder="Variable text..." 
                       class="w-100" @on-change="updateVariableData(row, index)" />

                <!-- Integer Input Value -->
                <InputNumber v-else-if="row.type == 'Integer'" v-model.number="row.value.number" size="small" 
                        placeholder="Variable number..." class="w-100" @on-change="updateVariableData(row, index)">
                </InputNumber>

                <!-- True/False Boolean Selector -->
                <Select v-else-if="row.type == 'Boolean'" v-model="row.value.boolean" size="small" class="w-100"
                        @on-change="updateVariableData(row, index)">
                    <Option v-for="boolean in booleanTypes" :value="boolean.value" :key="boolean.value">{{ boolean.name }}</Option>
                </Select>

                <span v-else-if="row.type == 'Custom'" class="d-flex">
                    <Icon type="ios-code-working" :size="20" class="mr-1" />
                    <span>Custom Code</span>
                </span>

            </template>

            <!-- Property Action -->
            <template slot-scope="{ row, index }" slot="action">
                <Button type="primary" size="small" class="mr-1"
                        :disabled="!['String', 'Custom'].includes(row.type)" 
                        @click="handleOpenEditVariableModal(builder.global_variables[index])">Edit</Button>
                <Button type="error" size="small" @click="handleConfirmRemoveVariable(index)">Delete</Button>
            </template>

        </Table>

        <!-- 
            MODAL TO EDIT VARIABLE
        -->
        <template v-if="isOpenEditVariableModal">

            <editVariableModal
                :index="index"
                :builder="builder"
                :variable="variable"
                @visibility="isOpenEditVariableModal = $event">
            </editVariableModal>

        </template>

    </Card>

</template>

<script>

    import editVariableModal from './editVariableModal.vue';
    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { editVariableModal, basicButton },
        props: {
            builder: {
                type: Object,
                default: null
            },
        },
        data(){

            return {
                index: null,
                variable: null,
                isOpenEditVariableModal: false,
                dataTypes: ['String', 'Integer', 'Boolean', 'Custom'],
                booleanTypes: [
                    {
                        name: 'True',
                        value: 'true',
                    },
                    {
                        name: 'False',
                        value: 'false',
                    }
                ],
                columns: [
                    {
                        title: 'Name',
                        slot: 'name',
                        key: 'name'
                    },
                    {
                        title: 'Type',
                        slot: 'type',
                        key: 'type'
                    },
                    {
                        title: 'Value',
                        slot: 'value',
                        key: 'value'
                    },
                    {
                        title: 'Action',
                        slot: 'action'
                    }
                ],
            }
        },
        computed: {
            variablesExist(){
                return this.builder.global_variables.length ? true : false;
            },
            addButtonType(){
                return this.variablesExist ? 'primary' : 'success';
            }
        },
        methods: {
            /** Note that the table data attribute does not offer two-way binding, this means that when
             *  we edit the "row" data e,g "row.name" or "row.type", we are only editing the local 
             *  value within the child <Table> component. This means that we are not updating the 
             *  global_variables data of the current component. To do so, we must manually listen 
             *  for changes on each input or select field, then update the global_variables.
             */
            updateVariableData(variable, index){
                this.$set(this.builder.global_variables, index, variable);
            },
            handleAddVariable(){
                this.builder.global_variables.push({
                    name: null,
                    type: null,
                    value: {
                        string: null,
                        number: null,
                        boolean: null,
                        code: null
                    },
                });

                this.$Message.success({
                    content: 'Variable added!',
                    duration: 6
                });

            },
            handleConfirmRemoveVariable(index){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the variable removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Variable',
                    onOk: () => { this.handleRemoveVariable(index) },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, (self.builder.global_variables[index] || {}).name),
                                '". After this variable is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveVariable(index){
                this.builder.global_variables.splice(index, 1);

                this.$Message.success({
                    content: 'Variable removed!',
                    duration: 6
                });
            },
            handleOpenEditVariableModal(variable) {
                this.variable = variable;
                this.isOpenEditVariableModal = true;
            }
        }
    }
</script>
