import React, { useState, useEffect } from 'react';
import { Card, Space, Spin, Typography } from 'antd';
import { LoadingOutlined } from '@ant-design/icons'

const { Title } = Typography;


function ExtractIFrame(iFrameText, onLoaded) {
    const urlStartIndex = iFrameText.indexOf("src") + 5;
    const urlEndIndex = iFrameText.indexOf("\"", urlStartIndex + 1);

    const url = iFrameText.substring(urlStartIndex, urlEndIndex).replace('amp;', '');

    return (
        <div style={{ overflow: 'hidden', background: 'transparent', margin: 0, padding: 0 }}>
            <iframe
                src={url}
                onLoad={() => { onLoaded(); }}
                style={{
                    border: 'none',
                    margin: '0 15px',
                    overflow: 'hidden',
                    width: '330px',
                    height: '550px'
                }} />
        </div>
    );
}


const CampaignPreviewComponentView = (props) => {

    const [loading, setLoading] = useState(true);

    const iFrame = ExtractIFrame(props.text, () => { setLoading(false); });

    return (
        <Spin spinning={loading}>
            {iFrame}
        </Spin>
    );
};

const CampaignPreviewView = (props) => {

    const [result, setResult] = useState({});

    const url = facebook_for_woocommerce_settings_advertise_asc.ajax_url + (
        props.preview ? (
            '?action=wc_facebook_get_ad_preview&view=' + props.campaignType
        ) : (
            '?action=wc_facebook_generate_ad_preview&view=' + props.campaignType + '&message=' + encodeURIComponent(props.message)
        ));

    useEffect(() => {
        fetch(url)
            .then((response) => response.json())
            .then((data) => {
                setResult(data);
            })
            .catch((err) => {
                //console.log(err.message);
            });
    }, [setResult]);

    if (result && result["data"]) {
        props.onSizeChange(1150, 600);
        return (
            <Space direction='horizontal'>
                <>
                    {result["data"].map(function (o, i) {
                        return (<CampaignPreviewComponentView text={o} />);
                    })}
                </>
            </Space>
        );
    }
    else {
        props.onSizeChange(750, 600);
        return (
            <Card style={{ width: '700px', height: '550px', display: 'inline-block' }}>
                <div style={{ width: '650px', height: '475px', alignItems: 'center', justifyContent: 'center', display: 'flex' }}>
                    <div>
                        <Title><LoadingOutlined /> Loading...</Title>
                    </div>
                </div>
            </Card>
        );
    };
};

export default CampaignPreviewView;