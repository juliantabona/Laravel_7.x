<template>

    <Row :gutter="12" class="border-bottom pb-2">
        <Col :span="24">
            <Tabs value="editor" class="builder-main-tabs">

                <!-- Editor -->
                <TabPane label="Editor" name="editor" icon="ios-git-branch">

                    <editor class="p-2"></editor>

                </TabPane>

                <!-- Simulator -->
                <TabPane label="Simulator" name="simulator" icon="ios-phone-portrait">

                </TabPane>
                
            </Tabs>
        </Col>
    </Row>

</template>

<script>

    import editor from './editor/main.vue'; 

    export default {
        components: { editor },
        data(){
            return {

            }
        },
    }
</script>
