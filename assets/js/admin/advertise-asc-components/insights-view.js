import React, { useState } from 'react';
import { Button, Card, Form, Modal, Select, Space, Spin, Switch, Tooltip } from 'antd';
import { QuestionCircleFilled } from '@ant-design/icons'
import { CountryList } from './eligible-country-list'
import CampaignPreviewView from './campaign-preview-view'

function UpdateState(callback, campaignType, state) {
    
    const requestData = JSON.stringify({
        campaignType: campaignType,
        status: String(state),
    });
    
    fetch(facebook_for_woocommerce_settings_advertise_asc.ajax_url + '?action=wc_facebook_update_ad_status', {
        method: 'post',
        headers: { 'Content-Type': 'application/json' },
        body: requestData
    })
        .then((response) => response.json())
        .then((data) => {
            if (!data['success']) {
                console.log(data);
            }

            callback();
        })
        .catch((err) => {
            //TODO: HANDLE ERROR
            console.log(err.message);
        });

}

const FunnelComponentView = (props) => {

    const sum = props.reach + props.clicks + props.views + props.addToCarts + props.purchases;
    return (
        <table class="bar-chart funnel-table">
            <thead style={{ height: '90%' }}>
                <th><div style={{ marginRight:'20px', width:'50px', height: 200.0 * (props.reach / sum) + 'px' }}></div></th>
                <th><div style={{ marginRight:'20px',width:'50px',height: 200.0 * (props.clicks / sum) + 'px' }}></div></th>
                <th><div style={{ marginRight:'20px',width:'50px',height: 200.0 * (props.views / sum) + 'px' }}></div></th>
                <th><div style={{ marginRight:'20px',width:'50px',height: 200.0 * (props.addToCarts / sum) + 'px' }}></div></th>
                <th><div style={{ marginRight:'20px',width:'50px',height: 200.0 * (props.purchases / sum) + 'px' }}></div></th>
                <th></th>
            </thead>
            <tbody style={{ height: '10%' }}>
                <tr>
                    <td><Space class="funnel-table-header" direction='vertical'><label>Reach</label><label>{props.reach}</label></Space></td>
                    <td><Space class="funnel-table-header" direction='vertical'><label>Clicks</label><label>{props.clicks}</label></Space></td>
                    <td><Space class="funnel-table-header" direction='vertical'><label>Views</label><label>{props.views}</label></Space></td>
                    <td><Space class="funnel-table-header" direction='vertical'><label>Add to cart</label><label>{props.addToCarts}</label></Space></td>
                    <td><Space class="funnel-table-header" direction='vertical'><label>Purchase</label><label>{props.purchases}</label></Space></td>
                    <td><Tooltip title="X-through rate, Cost per action"><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} /></Tooltip></td>
                </tr>
            </tbody>
        </table>
    );
}

const InsightsView = (props) => {

    const countryPairs = CountryList.map((c) => {
        return {
            key: Object.keys(c)[0],
            value: Object.values(c)[0]
        }
    });
    const selectedCountries = countryPairs.filter(c => { return props.countryList.indexOf(c.key) >= 0 });
    const [loading, setLoading] = useState(false);
    const [state, setState] = useState(props.status);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [width, setWidth] = useState(0);
    const [height, setHeight] = useState(0);

    const onEditButtonClicked = () => {
        window.editCampaignButtonClicked(props.campaignType);
    };
    const onStatusChange = (newValue) => {
        setState(newValue);
        setLoading(true);
        UpdateState(() => { setLoading(false) }, props.campaignType, newValue);
    };

    const showAdPreview = () => {
        setIsModalOpen(true);
    };

    return (
        <>
            <Card style={{ marginBottom: '20px' }}>
                <Space direction='vertical' style={{ width: '100%' }}>
                    <Form>
                        <Space direction='horizontal' style={{ width: '100%' }}>
                            <table style={{ textAlign: 'center', verticalAlign: 'top' }}>
                                <thead >
                                    <th><p style={{ margin: '0 20px', padding: 0 }}>Status</p></th>
                                    <th><p style={{ margin: '0 20px', padding: 0 }}>Spend</p></th>
                                    <th><p style={{ margin: '0 20px', padding: 0 }}>Daily Budget</p></th>
                                    {props.campaignType == 'retargeting' ? (<></>) :(<th><p style={{ margin: '0 20px', padding: 0 }}>Country</p></th>)}
                                    
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <Spin spinning={loading} style={{ margin: 0, padding: 0 }}>
                                                <Form.Item style={{ margin: 0, padding: 0 }}>
                                                    <Switch checked={state}
                                                        style={{ margin: 0, padding: 0 }}
                                                        checkedChildren="On"
                                                        unCheckedChildren="Off"
                                                        onChange={(e) => { onStatusChange(e); }} />
                                                </Form.Item>
                                            </Spin>
                                        </td>
                                        <td><p style={{ margin: 0, padding: 0 }}>{props.spend} {props.currency}</p></td>
                                        <td><p style={{ margin: 0, padding: 0 }}>{props.dailyBudget} {props.currency}</p></td>
                                        {props.campaignType == 'retargeting' ? (<></>) :(
                                        <td>
                                            <Select mode="multiple"
                                                showSearch={false}
                                                maxTagCount={5}
                                                onChange={() => { }}
                                                value={selectedCountries.map((item) => ({
                                                    value: item['key'],
                                                    label: item['value']
                                                }))}
                                                style={{ marginTop: 0, width: '100%', maxWidth: '200px', maxHeight: '150px', whiteSpace: 'nowrap', overflow: 'auto' }}
                                                options={selectedCountries.map((item) => ({
                                                    value: item['key'],
                                                    label: item['value']
                                                }))} />
                                        </td>)}
                                    </tr>
                                </tbody>
                            </table>
                            {props.reach > 0 ? (
                                <FunnelComponentView reach={props.reach} clicks={props.clicks} views={props.views} addToCarts={props.addToCarts} purchases={props.purchases} />
                            ) : (
                                <></>
                            )}
                        </Space>
                    </Form>
                    <Space direction='horizontal'>
                        <Button onClick={showAdPreview}>Show Ad</Button>
                        <Button onClick={onEditButtonClicked}>Edit</Button>
                    </Space>
                </Space>
            </Card >
            <Modal title="Ad Preview" open={isModalOpen} onCancel={() => { setIsModalOpen(false); }} footer={<></>} width={width} height={height}>
                <CampaignPreviewView preview={true} campaignType={props.campaignType} onSizeChange={(w, h) => {
                    setWidth(w);
                    setHeight(h);
                }} />
            </Modal>
        </>
    );
}

export default InsightsView;