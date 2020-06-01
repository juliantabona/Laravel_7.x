<template>

    <Row :gutter="12">

        <Col :span="7">

            <Card :bordered="false" class="mb-1">

                <!-- Version -->    
                <div class="d-flex">

                    <span class="d-block mt-2 font-weight-bold text-dark mr-2">Edit: </span>

                    <Select v-model="edit_version" filterable placeholder="Select version" class="mr-2">

                        <Option v-for="(version, key) in versions" :key="key"
                                :value="version.name" :label="version.name">
                        </Option>

                    </Select>

                    <!-- Add Version Button -->
                    <Button class="p-1">
                        <Icon type="ios-add" :size="20" />
                    </Button>

                </div>
                
            </Card>

            <Card :bordered="false">

                <!-- Screen Menu Heading -->    
                <div slot="title" class="clearfix pb-2 mb-3 border-bottom">

                    <span class="d-block mt-2 font-weight-bold text-dark float-left">Screens</span>

                    <!-- Create Screen Button -->
                    <Button class="p-1 float-right">
                        <Icon type="ios-add" :size="20" />
                    </Button>

                </div>

                <CellGroup>
                    <Cell title="Global Variables" />
                    <Cell title="Live Version" />
                    <Cell title="Live Mode">
                        <i-Switch v-model="switchValue" slot="extra" />
                    </Cell>
                </CellGroup>
                
            </Card>

        </Col>

        <Col :span="17">

            <Card :bordered="false">

                <span class="d-block mt-2 font-weight-bold text-dark">Settings</span>
                
            </Card>

        </Col>

    </Row>

</template>

<script>

    export default {
        data(){
            return {
                switchValue: false,
                edit_version: 'Version 3',
                deploy_version: 'Version 1',
                versions: [
                    {
                        name: 'Version 1',
                    },
                    {
                        name: 'Version 2',
                    },
                    {
                        name: 'Version 3',
                    }
                ]
            }
        },
    }
</script>
