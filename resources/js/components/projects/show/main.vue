<template>
    <Layout class="border-top" :style="{ minHeight:'100em' }">
        <Header :style="{width: '100%'}" class="bg-white border-top border-bottom p-0">
            <Row :gutter="12">
                <Col :span="16" :offset="2">
                    <Breadcrumb>
                        <BreadcrumbItem to="/projects">Projects</BreadcrumbItem>
                        <BreadcrumbItem to="/projects">Project 1</BreadcrumbItem>
                        <BreadcrumbItem to="/projects">Builder</BreadcrumbItem>
                    </Breadcrumb>
                </Col>
                <Col :span="4" class="clearfix">
                    <Button type="primary" icon="ios-search" size="large" class="float-right mt-2">Save</Button>
                </Col>
            </Row>
        </Header>
        <Layout>
            <Sider hide-trigger>
                <Menu active-name="1" theme="light" width="auto">
                    <MenuItem name="1">
                        <Icon type="ios-analytics-outline" :size="20" />
                        Overview
                    </MenuItem>
                    <MenuItem name="2">
                        <Icon type="ios-git-branch" :size="20" />
                        Builder
                    </MenuItem>
                    <MenuItem name="3">
                        <Icon type="ios-cash-outline" :size="20" />
                        Billing
                    </MenuItem>
                    <MenuItem name="4">
                        <Icon type="ios-stats-outline" :size="20" />
                        Analytics
                    </MenuItem>
                    <MenuItem name="5">
                        <Icon type="ios-chatboxes-outline" :size="20" />
                        Subscriptions
                    </MenuItem>
                </Menu>
            </Sider>
            <Content>
                <Row :gutter="12" class="border-bottom pb-2">
                    <Col :span="24">
                        <Tabs value="editor" class="builder-main-tabs">
                            <TabPane label="Editor" name="editor" icon="ios-git-branch">

                                <editor class="p-2"></editor>

                            </TabPane>
                            <TabPane label="Simulator" name="simulator" icon="ios-phone-portrait">

                            </TabPane>
                        </Tabs>
                    </Col>
                </Row>
            </Content>
        </Layout>
    </Layout>

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
